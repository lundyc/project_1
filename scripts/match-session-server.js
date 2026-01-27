#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');
const http = require('http');
const crypto = require('crypto');
const express = require('express');
const { Server } = require('socket.io');
const mysql = require('mysql2/promise');
const dotenv = require('dotenv');

dotenv.config();

const PROJECT_ROOT = path.resolve(__dirname, '..');

function toInt(value, fallback) {
  const parsed = Number.parseInt(String(value ?? ''), 10);
  return Number.isFinite(parsed) ? parsed : fallback;
}

function toFloat(value, fallback) {
  const parsed = Number.parseFloat(String(value ?? ''));
  return Number.isFinite(parsed) ? parsed : fallback;
}

function toBool(value, fallback = false) {
  if (value === undefined || value === null || value === '') {
    return fallback;
  }
  const normalized = String(value).trim().toLowerCase();
  if (['1', 'true', 'yes', 'on'].includes(normalized)) {
    return true;
  }
  if (['0', 'false', 'no', 'off'].includes(normalized)) {
    return false;
  }
  return fallback;
}

function base64UrlEncode(input) {
  return Buffer.from(input).toString('base64url');
}

function base64UrlDecode(input) {
  return Buffer.from(String(input || ''), 'base64url').toString('utf8');
}

function readPhpDbConfig() {
  const configPath = path.join(PROJECT_ROOT, 'config', 'config.php');
  if (!fs.existsSync(configPath)) {
    return null;
  }
  const raw = fs.readFileSync(configPath, 'utf8');
  const dbBlockMatch = raw.match(/'db'\s*=>\s*\[(.*?)\],/s);
  const dbBlock = dbBlockMatch ? dbBlockMatch[1] : raw;
  const extract = (key) => {
    const pattern = new RegExp(`'${key}'\\s*=>\\s*'([^']*)'`, 'i');
    const match = dbBlock.match(pattern);
    return match ? match[1] : null;
  };
  const host = extract('host');
  const name = extract('name');
  const user = extract('user');
  const pass = extract('pass');
  const charset = extract('charset') || 'utf8mb4';
  if (!host || !name || !user) {
    return null;
  }
  return { host, name, user, pass: pass || '', charset };
}

function resolveDbConfig() {
  const phpConfig = readPhpDbConfig() || {};
  const host = process.env.MATCH_SESSION_DB_HOST || process.env.DB_HOST || phpConfig.host;
  const database = process.env.MATCH_SESSION_DB_NAME || process.env.DB_NAME || phpConfig.name;
  const user = process.env.MATCH_SESSION_DB_USER || process.env.DB_USER || phpConfig.user;
  const password = process.env.MATCH_SESSION_DB_PASS || process.env.DB_PASS || phpConfig.pass;
  const port = toInt(process.env.MATCH_SESSION_DB_PORT || process.env.DB_PORT, 3306);
  const charset = process.env.MATCH_SESSION_DB_CHARSET || phpConfig.charset || 'utf8mb4';
  if (!host || !database || !user) {
    return null;
  }
  return { host, port, user, password, database, charset };
}

const PORT = toInt(process.env.MATCH_SESSION_PORT || process.env.PORT, 4001);
const HOST = process.env.MATCH_SESSION_HOST || '0.0.0.0';
const ORIGIN = process.env.MATCH_SESSION_ORIGIN || true;
const SECRET = process.env.MATCH_SESSION_SECRET || 'dev-session-secret-change-me';
const PUBLIC_WS_URL = process.env.MATCH_SESSION_WS_URL || '';
const STATE_TICK_MS = toInt(process.env.MATCH_SESSION_STATE_TICK_MS, 1_000);
const PERSIST_DEBOUNCE_MS = toInt(process.env.MATCH_SESSION_PERSIST_DEBOUNCE_MS, 750);
const AUTO_CONTROL_ON_ANALYST_JOIN = toBool(process.env.MATCH_SESSION_AUTO_CONTROL_ON_ANALYST_JOIN, true);
const PAUSE_ON_ANALYST_JOIN = toBool(process.env.MATCH_SESSION_PAUSE_ON_ANALYST_JOIN, true);

const dbConfig = resolveDbConfig();
let dbPool = null;

async function initDb() {
  if (!dbConfig) {
    console.warn('[match-session] DB config not found; running with in-memory sessions only.');
    return;
  }
  dbPool = mysql.createPool({
    host: dbConfig.host,
    port: dbConfig.port,
    user: dbConfig.user,
    password: dbConfig.password,
    database: dbConfig.database,
    charset: dbConfig.charset,
    waitForConnections: true,
    connectionLimit: 5,
    maxIdle: 5,
    idleTimeout: 30_000,
  });

  const createSql = `
    CREATE TABLE IF NOT EXISTS match_sessions (
      match_id INT NOT NULL PRIMARY KEY,
      playing TINYINT(1) NOT NULL DEFAULT 0,
      base_time_seconds DOUBLE NOT NULL DEFAULT 0,
      playback_rate DOUBLE NOT NULL DEFAULT 1,
      updated_at_ms BIGINT NOT NULL,
      control_owner_user_id INT NULL,
      control_owner_name VARCHAR(255) NULL,
      control_owner_socket_id VARCHAR(128) NULL,
      control_expires_at_ms BIGINT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  `;
  await dbPool.query(createSql);
  console.log('[match-session] DB ready');
}

async function loadSessionSnapshot(matchId) {
  if (!dbPool) {
    return null;
  }
  const [rows] = await dbPool.query(
    `SELECT match_id, playing, base_time_seconds, playback_rate, updated_at_ms,
            control_owner_user_id, control_owner_name, control_owner_socket_id, control_expires_at_ms
       FROM match_sessions
      WHERE match_id = ?
      LIMIT 1`,
    [matchId]
  );
  if (!rows || rows.length === 0) {
    return null;
  }
  const row = rows[0];
  return {
    matchId: Number(row.match_id),
    playing: Boolean(row.playing),
    baseTime: toFloat(row.base_time_seconds, 0),
    rate: toFloat(row.playback_rate, 1),
    updatedAt: toInt(row.updated_at_ms, Date.now()),
    controlOwner: row.control_owner_user_id
      ? {
        userId: Number(row.control_owner_user_id),
        userName: row.control_owner_name || 'Analyst',
        socketId: row.control_owner_socket_id || null,
      }
      : null,
    controlExpiresAt: row.control_expires_at_ms ? Number(row.control_expires_at_ms) : null,
  };
}

async function persistSessionSnapshot(session) {
  if (!dbPool) {
    return;
  }
  const snapshot = session.toSnapshot();
  const controlOwnerUserId = snapshot.controlOwner ? snapshot.controlOwner.userId : null;
  const controlOwnerName = snapshot.controlOwner ? snapshot.controlOwner.userName : null;
  const controlOwnerSocketId = snapshot.controlOwner ? snapshot.controlOwner.socketId : null;
  const controlExpiresAt = snapshot.controlOwner ? snapshot.controlOwner.expiresAt : null;

  await dbPool.query(
    `INSERT INTO match_sessions (
        match_id, playing, base_time_seconds, playback_rate, updated_at_ms,
        control_owner_user_id, control_owner_name, control_owner_socket_id, control_expires_at_ms
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE
        playing = VALUES(playing),
        base_time_seconds = VALUES(base_time_seconds),
        playback_rate = VALUES(playback_rate),
        updated_at_ms = VALUES(updated_at_ms),
        control_owner_user_id = VALUES(control_owner_user_id),
        control_owner_name = VALUES(control_owner_name),
        control_owner_socket_id = VALUES(control_owner_socket_id),
        control_expires_at_ms = VALUES(control_expires_at_ms)`,
    [
      snapshot.matchId,
      snapshot.playing ? 1 : 0,
      snapshot.baseTime,
      snapshot.rate,
      snapshot.updatedAt,
      controlOwnerUserId,
      controlOwnerName,
      controlOwnerSocketId,
      controlExpiresAt,
    ]
  );
}

function signPayload(payloadJson) {
  return crypto.createHmac('sha256', SECRET).update(payloadJson).digest('base64url');
}

function verifyToken(token, expectedMatchId) {
  if (!token || typeof token !== 'string') {
    throw new Error('Missing token');
  }
  const [payloadEncoded, signature] = token.split('.');
  if (!payloadEncoded || !signature) {
    throw new Error('Malformed token');
  }
  const payloadJson = base64UrlDecode(payloadEncoded);
  const expectedSignature = signPayload(payloadJson);
  const sigBuf = Buffer.from(signature);
  const expectedBuf = Buffer.from(expectedSignature);
  if (sigBuf.length !== expectedBuf.length || !crypto.timingSafeEqual(sigBuf, expectedBuf)) {
    throw new Error('Invalid signature');
  }
  const payload = JSON.parse(payloadJson);
  const now = Date.now();
  if (payload.exp && now > Number(payload.exp)) {
    throw new Error('Token expired');
  }
  if (expectedMatchId && Number(payload.matchId) !== Number(expectedMatchId)) {
    throw new Error('Token match mismatch');
  }
  return payload;
}

function roomForMatch(matchId) {
  return `match:${matchId}`;
}

function clampTime(value, duration) {
  if (!Number.isFinite(value)) {
    return 0;
  }
  const minClamped = Math.max(0, value);
  if (!Number.isFinite(duration) || duration <= 0) {
    return minClamped;
  }
  return Math.min(duration, minClamped);
}

class MatchSession {
  constructor(io, matchId, snapshot) {
    this.io = io;
    this.matchId = Number(matchId);
    const now = Date.now();
    this.playing = snapshot?.playing ?? false;
    this.baseTime = snapshot?.baseTime ?? 0;
    this.rate = snapshot?.rate ?? 1;
    this.updatedAt = snapshot?.updatedAt ?? now;
    this.durationSeconds = null;
    this.controlOwner = null;
    this.stateTick = null;
    this.persistTimer = null;
    this.sockets = new Map();

    if (snapshot?.controlOwner) {
      this.controlOwner = {
        userId: snapshot.controlOwner.userId,
        userName: snapshot.controlOwner.userName,
        socketId: snapshot.controlOwner.socketId || null,
      };
    }

    if (this.playing) {
      this.ensureStateTick();
    }
  }

  setDurationSeconds(durationSeconds) {
    const normalized = toFloat(durationSeconds, NaN);
    if (Number.isFinite(normalized) && normalized > 0) {
      this.durationSeconds = normalized;
    }
  }

  getServerNow() {
    return Date.now();
  }

  getProjectedTime(atMs = this.getServerNow()) {
    const elapsedMs = Math.max(0, atMs - this.updatedAt);
    const elapsedSeconds = (elapsedMs / 1000) * this.rate;
    const projected = this.playing ? this.baseTime + elapsedSeconds : this.baseTime;
    return clampTime(projected, this.durationSeconds);
  }

  setBaseTime(timeSeconds, atMs = this.getServerNow()) {
    this.baseTime = clampTime(timeSeconds, this.durationSeconds);
    this.updatedAt = atMs;
  }

  toSnapshot(atMs = this.getServerNow()) {
    const time = this.getProjectedTime(atMs);
    return {
      matchId: this.matchId,
      playing: this.playing,
      time,
      baseTime: this.baseTime,
      rate: this.rate,
      updatedAt: this.updatedAt,
      controlOwner: this.controlOwner
        ? {
          userId: this.controlOwner.userId,
          userName: this.controlOwner.userName,
          socketId: this.controlOwner.socketId,
        }
        : null,
    };
  }

  broadcastState(reason = 'update') {
    const serverTime = this.getServerNow();
    const snapshot = this.toSnapshot(serverTime);
    const payload = {
      matchId: snapshot.matchId,
      playing: snapshot.playing,
      time: snapshot.time,
      baseTime: snapshot.baseTime,
      rate: snapshot.rate,
      updatedAt: snapshot.updatedAt,
      controlOwner: snapshot.controlOwner,
      serverTime,
      reason,
    };
    this.io.to(roomForMatch(this.matchId)).emit('session_state', payload);
    this.schedulePersist();
  }

  ensureStateTick() {
    if (this.stateTick) {
      return;
    }
    this.stateTick = setInterval(() => {
      this.broadcastState('tick');
    }, STATE_TICK_MS);
  }

  clearStateTick() {
    if (!this.stateTick) {
      return;
    }
    clearInterval(this.stateTick);
    this.stateTick = null;
  }

  schedulePersist() {
    if (!dbPool) {
      return;
    }
    if (this.persistTimer) {
      clearTimeout(this.persistTimer);
    }
    this.persistTimer = setTimeout(() => {
      this.persistTimer = null;
      persistSessionSnapshot(this).catch((err) => {
        console.error('[match-session] Persist failed', this.matchId, err?.message || err);
      });
    }, PERSIST_DEBOUNCE_MS);
  }

  setControlOwner(owner) {
    const previous = this.controlOwner;
    const changed =
      !previous || Number(previous.userId) !== Number(owner.userId) || previous.socketId !== owner.socketId;
    this.controlOwner = {
      userId: owner.userId,
      userName: owner.userName || 'Analyst',
      socketId: owner.socketId,
    };
    this.schedulePersist();
    return { owner: this.controlOwner, changed, previous };
  }

  refreshControlOwner(owner) {
    if (!this.controlOwner) {
      return this.setControlOwner(owner);
    }
    const isSameOwner =
      Number(this.controlOwner.userId) === Number(owner.userId) && this.controlOwner.socketId === owner.socketId;
    if (!isSameOwner) {
      return this.setControlOwner(owner);
    }
    const previous = this.controlOwner;
    this.schedulePersist();
    return { owner: this.controlOwner, changed: false, previous };
  }

  releaseControl(owner, reason = 'released') {
    if (!this.controlOwner) {
      return false;
    }
    const matchesOwner =
      Number(this.controlOwner.userId) === Number(owner.userId) && this.controlOwner.socketId === owner.socketId;
    if (!matchesOwner) {
      return false;
    }
    const previous = this.controlOwner;
    this.controlOwner = null;
    this.schedulePersist();
    this.io.to(roomForMatch(this.matchId)).emit('control_owner_changed', {
      matchId: this.matchId,
      controlOwner: null,
      previousControlOwner: previous,
      serverTime: this.getServerNow(),
      reason,
    });
    this.broadcastState('control_released');
    return true;
  }

  canTakeControl(requester) {
    if (!this.controlOwner) {
      return { ok: true, reason: 'available' };
    }
    // Single-operator mode: allow takeover, especially for the same user
    // reconnecting from a new socket.
    if (Number(this.controlOwner.userId) === Number(requester.userId)) {
      return { ok: true, reason: 'same_user_takeover', owner: this.controlOwner };
    }
    return { ok: true, reason: 'takeover', owner: this.controlOwner };
    const isSameOwner =
      Number(this.controlOwner.userId) === Number(requester.userId) && this.controlOwner.socketId === requester.socketId;
    if (isSameOwner) {
      return { ok: true, reason: 'already_owner' };
    }
    return { ok: false, reason: 'owned_by_other', owner: this.controlOwner };
  }

  attachSocket(socket, userInfo) {
    this.sockets.set(socket.id, userInfo);
    this.setDurationSeconds(userInfo.durationSeconds);
  }

  detachSocket(socketId) {
    const userInfo = this.sockets.get(socketId);
    this.sockets.delete(socketId);
    if (
      this.controlOwner &&
      userInfo &&
      Number(this.controlOwner.userId) === Number(userInfo.userId) &&
      this.controlOwner.socketId === socketId
    ) {
      const previous = this.controlOwner;
      this.controlOwner = null;
      this.io.to(roomForMatch(this.matchId)).emit('control_owner_changed', {
        matchId: this.matchId,
        controlOwner: null,
        previousControlOwner: previous,
        serverTime: this.getServerNow(),
        reason: 'disconnect',
      });
      this.broadcastState('owner_disconnect');
    }
    if (!this.playing) {
      this.clearStateTick();
    }
  }

  applyPlaybackCommand(cmd) {
    const now = this.getServerNow();
    const currentTime = this.getProjectedTime(now);
    switch (cmd.type) {
      case 'play': {
        this.playing = true;
        this.setBaseTime(currentTime, now);
        this.ensureStateTick();
        break;
      }
      case 'pause': {
        this.playing = false;
        this.setBaseTime(currentTime, now);
        this.clearStateTick();
        break;
      }
      case 'seek': {
        const targetTime = clampTime(toFloat(cmd.time, currentTime), this.durationSeconds);
        this.setBaseTime(targetTime, now);
        if (this.playing) {
          this.ensureStateTick();
        }
        break;
      }
      case 'rate': {
        const nextRate = toFloat(cmd.rate, this.rate);
        const safeRate = Number.isFinite(nextRate) && nextRate > 0 ? nextRate : 1;
        this.setBaseTime(currentTime, now);
        this.rate = safeRate;
        if (this.playing) {
          this.ensureStateTick();
        }
        break;
      }
      default:
        return { ok: false, error: 'unknown_command' };
    }

    this.broadcastState(`cmd:${cmd.type}`);
    return { ok: true };
  }
}

class SessionManager {
  constructor(io) {
    this.io = io;
    this.sessions = new Map();
  }

  async getOrCreate(matchId) {
    const key = Number(matchId);
    const existing = this.sessions.get(key);
    if (existing) {
      return existing;
    }
    const snapshot = await loadSessionSnapshot(key).catch((err) => {
      console.error('[match-session] Load snapshot failed', key, err?.message || err);
      return null;
    });
    const session = new MatchSession(this.io, key, snapshot);
    this.sessions.set(key, session);
    return session;
  }

  async getSnapshot(matchId) {
    const session = await this.getOrCreate(matchId);
    const serverTime = Date.now();
    const snapshot = session.toSnapshot(serverTime);
    return {
      matchId: snapshot.matchId,
      playing: snapshot.playing,
      time: snapshot.time,
      baseTime: snapshot.baseTime,
      rate: snapshot.rate,
      updatedAt: snapshot.updatedAt,
      controlOwner: snapshot.controlOwner,
      serverTime,
    };
  }
}

async function main() {
  await initDb();

  const app = express();
  app.use(express.json({ limit: '64kb' }));

  const server = http.createServer(app);
  const io = new Server(server, {
    cors: {
      origin: ORIGIN,
      credentials: true,
    },
  });

  const sessions = new SessionManager(io);

  app.get('/health', (_req, res) => {
    res.json({ ok: true, service: 'match-session', time: Date.now() });
  });

  app.get('/sessions/:matchId', async (req, res) => {
    const matchId = toInt(req.params.matchId, 0);
    if (!matchId) {
      res.status(400).json({ ok: false, error: 'invalid_match_id' });
      return;
    }
    try {
      const snapshot = await sessions.getSnapshot(matchId);
      res.json({ ok: true, snapshot });
    } catch (err) {
      console.error('[match-session] snapshot error', matchId, err?.message || err);
      res.status(500).json({ ok: false, error: 'snapshot_failed' });
    }
  });

  io.on('connection', (socket) => {
    console.log('[match-session] socket connected', socket.id);
    socket.on('session_join', async (payload = {}) => {
      try {
        const matchId = toInt(payload.matchId, 0);
        if (!matchId) {
          socket.emit('error', { code: 'invalid_match_id', message: 'Match ID is required.' });
          return;
        }
        const tokenPayload = verifyToken(payload.token, matchId);
        const role = tokenPayload.role === 'viewer' ? 'viewer' : 'analyst';
        const userId = toInt(tokenPayload.userId, 0);
        const userName = tokenPayload.userName || payload.userName || 'Analyst';
        const durationSeconds = toFloat(payload.durationSeconds, NaN);

        socket.data.matchId = matchId;
        socket.data.role = role;
        socket.data.userId = userId;
        socket.data.userName = userName;

        const session = await sessions.getOrCreate(matchId);
        session.attachSocket(socket, { userId, userName, role, durationSeconds });

        socket.join(roomForMatch(matchId));
        console.log('[match-session] session_join', { socketId: socket.id, matchId, role, userId });

        socket.emit('session_state', await sessions.getSnapshot(matchId));

        // Phase C: Remove or soften auto-pause-on-join behavior
        // Do NOT force pause when analyst joins; only assign control.
        // This prevents unnecessary playback churn and flicker on join.
        if (role === 'analyst' && AUTO_CONTROL_ON_ANALYST_JOIN) {
          const requester = { userId, userName, socketId: socket.id };
          const decision = session.canTakeControl(requester);
          const controlResult = session.setControlOwner(requester);

          console.log('[match-session] auto_control', {
            socketId: socket.id,
            matchId,
            reason: decision.reason,
          });

          io.to(roomForMatch(matchId)).emit('control_owner_changed', {
            matchId,
            controlOwner: controlResult.owner,
            previousControlOwner: controlResult.previous || null,
            serverTime: Date.now(),
            reason: `auto:${decision.reason}`,
          });

          socket.emit('control_granted', {
            matchId,
            controlOwner: controlResult.owner,
            previousControlOwner: controlResult.previous || null,
            serverTime: Date.now(),
            reason: `auto:${decision.reason}`,
          });

          session.broadcastState('auto_control');
          // No pause is issued here; playback state remains stable unless user acts.
        }

        if (session.controlOwner) {
          socket.emit('control_owner_changed', {
            matchId,
            controlOwner: session.controlOwner,
            serverTime: Date.now(),
            reason: 'join_sync',
          });
        }
      } catch (err) {
        console.error('[match-session] join error', err?.message || err);
        socket.emit('error', { code: 'join_failed', message: err?.message || 'Unable to join session.' });
      }
    });

    socket.on('session_state_request', async () => {
      const matchId = socket.data.matchId;
      if (!matchId) {
        return;
      }
      const snapshot = await sessions.getSnapshot(matchId);
      socket.emit('session_state', snapshot);
    });

    socket.on('request_control', async (payload = {}) => {
      const matchId = toInt(payload.matchId, socket.data.matchId || 0);
      if (!matchId || matchId !== socket.data.matchId) {
        socket.emit('control_denied', { matchId, reason: 'match_mismatch' });
        return;
      }
      if (socket.data.role !== 'analyst') {
        socket.emit('control_denied', { matchId, reason: 'role_forbidden' });
        return;
      }
      const session = await sessions.getOrCreate(matchId);
      const requester = { userId: socket.data.userId, userName: socket.data.userName, socketId: socket.id };
      console.log('[match-session] request_control', {
        socketId: socket.id,
        matchId,
        userId: socket.data.userId,
        currentOwner: session.controlOwner,
      });
      const decision = session.canTakeControl(requester);
      if (!decision.ok) {
        socket.emit('control_denied', {
          matchId,
          reason: decision.reason,
          controlOwner: decision.owner || null,
          serverTime: Date.now(),
        });
        return;
      }
      const controlResult = session.setControlOwner(requester);
      if (controlResult.changed || decision.reason !== 'already_owner') {
        io.to(roomForMatch(matchId)).emit('control_owner_changed', {
          matchId,
          controlOwner: controlResult.owner,
          previousControlOwner: controlResult.previous || null,
          serverTime: Date.now(),
          reason: decision.reason,
        });
      }
      socket.emit('control_granted', {
        matchId,
        controlOwner: controlResult.owner,
        previousControlOwner: controlResult.previous || null,
        serverTime: Date.now(),
      });
      session.broadcastState('control_granted');
    });

    socket.on('release_control', async (payload = {}) => {
      const matchId = toInt(payload.matchId, socket.data.matchId || 0);
      if (!matchId || matchId !== socket.data.matchId) {
        return;
      }
      const session = await sessions.getOrCreate(matchId);
      const owner = { userId: socket.data.userId, socketId: socket.id };
      const released = session.releaseControl(owner, 'released');
      if (!released) {
        socket.emit('control_denied', { matchId, reason: 'not_owner', serverTime: Date.now() });
      }
    });

    socket.on('playback_cmd', async (payload = {}) => {
      const matchId = toInt(payload.matchId, socket.data.matchId || 0);
      if (!matchId || matchId !== socket.data.matchId) {
        socket.emit('error', { code: 'match_mismatch', message: 'Match mismatch.' });
        return;
      }
      if (socket.data.role !== 'analyst') {
        socket.emit('error', { code: 'role_forbidden', message: 'Viewer cannot control playback.' });
        return;
      }
      console.log('[match-session] playback_cmd', {
        socketId: socket.id,
        matchId,
        type: payload.type,
        time: payload.time,
        rate: payload.rate,
      });
      const session = await sessions.getOrCreate(matchId);
      const requester = { userId: socket.data.userId, userName: socket.data.userName, socketId: socket.id };
      const controlDecision = session.canTakeControl(requester);
      if (!controlDecision.ok) {
        console.warn('[match-session] playback_cmd denied', {
          socketId: socket.id,
          matchId,
          reason: controlDecision.reason,
          owner: controlDecision.owner || null,
        });
        socket.emit('control_denied', {
          matchId,
          reason: controlDecision.reason,
          controlOwner: controlDecision.owner || null,
          serverTime: Date.now(),
        });
        return;
      }
      const controlResult = session.refreshControlOwner(requester);
      if (controlResult.changed) {
        io.to(roomForMatch(matchId)).emit('control_owner_changed', {
          matchId,
          controlOwner: controlResult.owner,
          previousControlOwner: controlResult.previous || null,
          serverTime: Date.now(),
          reason: 'command_takeover',
        });
      }

      const type = String(payload.type || '').toLowerCase();
      if (!['play', 'pause', 'seek', 'rate'].includes(type)) {
        socket.emit('error', { code: 'invalid_command', message: 'Unsupported command.' });
        return;
      }

      const cmd = {
        type,
        time: payload.time,
        rate: payload.rate,
      };
      const result = session.applyPlaybackCommand(cmd);
      if (!result.ok) {
        socket.emit('error', { code: result.error, message: 'Command failed.' });
      }
    });

    socket.on('disconnect', () => {
      const matchId = socket.data.matchId;
      console.log('[match-session] socket disconnected', { socketId: socket.id, matchId: matchId || null });
      if (!matchId) {
        return;
      }
      const session = sessions.sessions.get(Number(matchId));
      session?.detachSocket(socket.id);
    });
  });

  server.listen(PORT, HOST, () => {
    console.log(`[match-session] listening on http://${HOST}:${PORT}`);
    if (PUBLIC_WS_URL) {
      console.log(`[match-session] public websocket url: ${PUBLIC_WS_URL}`);
    } else {
      console.log('[match-session] public websocket url: (not set; PHP will derive it)');
    }
  });
}

main().catch((err) => {
  console.error('[match-session] fatal error', err?.message || err);
  process.exitCode = 1;
});
