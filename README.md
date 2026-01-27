# Server-Authoritative Match Session (Option 3)

This project now includes a server-authoritative playback session so multiple devices can stay in sync on the same match.

Key pieces:
- A Node + Socket.IO session server: `scripts/match-session-server.js`
- A PHP session bootstrap endpoint: `/api/matches/{id}/session`
- A dedicated TV viewer route: `/matches/{id}/tv`
- A client session layer: `public/assets/js/desk-session.js`

## 1. Run the WebSocket Session Server

Install dependencies (already done if you're up to date):

```bash
npm install
```

Start the session server:

```bash
npm run ws:server
```

By default it listens on port `4001`.

## 1b. Run as a Systemd Service

A systemd unit has been installed at:

```text
/etc/systemd/system/match-session.service
```

Useful commands:

```bash
systemctl status match-session.service
systemctl restart match-session.service
journalctl -u match-session.service -n 100 --no-pager
```

## 2. Environment Variables

You can run with sensible defaults, but these variables are supported.

Core session settings:
- `MATCH_SESSION_PORT` (default: `4001`)
- `MATCH_SESSION_HOST` (default: `0.0.0.0`)
- `MATCH_SESSION_ORIGIN` (default: allow all origins)
- `MATCH_SESSION_SECRET` (default: `dev-session-secret-change-me`)

Server timing + control lock:
- `MATCH_SESSION_CONTROL_TTL_MS` (default: `10000`)
- `MATCH_SESSION_STATE_TICK_MS` (default: `1000`)
- `MATCH_SESSION_PERSIST_DEBOUNCE_MS` (default: `750`)

Database overrides (optional):
- `MATCH_SESSION_DB_HOST`
- `MATCH_SESSION_DB_PORT`
- `MATCH_SESSION_DB_NAME`
- `MATCH_SESSION_DB_USER`
- `MATCH_SESSION_DB_PASS`
- `MATCH_SESSION_DB_CHARSET`

PHP session bootstrap settings:
- `MATCH_SESSION_WS_URL`
  - Example: `https://your-domain:4001`
  - If not set, PHP will build one from the current host + `MATCH_SESSION_PORT`.
- `MATCH_SESSION_TOKEN_TTL_MS` (default: 6 hours)

Important: `MATCH_SESSION_SECRET` must match on both the PHP side and the Node server.

## 3. TV / Viewer Mode

Open the viewer-only TV route for a match:

```text
/matches/{id}/tv
```

Properties:
- Video only
- No playback controls
- Pointer events disabled on the video surface
- Viewer role enforced server-side (cannot control playback)

## 4. Analyst Mode (Existing Desk)

Use the existing desk route:

```text
/matches/{id}/desk
```

Playback behavior changes:
- Playback commands go to the server first
- The server validates control ownership and lock TTL
- Clients only update the video element from `session_state`
- Tagging uses server session time via `DeskSession.getCurrentSecond()`

## 5. Persistence Table

The session server persists session state to MySQL table `match_sessions`.

Reference SQL:

```text
sql/match_sessions.sql
```

The Node server will also create this table automatically if it does not exist.
