<?php
require_once __DIR__ . '/../app/lib/auth.php';
auth_boot();
require_auth();

echo password_hash('lol1234', PASSWORD_BCRYPT);

$title = 'Video Player Test';

$headExtras = <<<'HTML'
<style>
html, body {
  margin: 0;
  padding: 0;
  background: #020712;
  color: #fff;
}

.player {
  width: 100%;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.player-shell {
  width: 960px;
  max-width: 95vw;
  aspect-ratio: 16 / 9;
  background: #020712;
  border-radius: 16px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.viewer {
  position: relative;
  flex: 1;
  background: #000;
}

.viewer video {
  position: absolute;
  left: -9999px;
  width: 1px;
  height: 1px;
  opacity: 0;
}

#threeMount {
  position: absolute;
  inset: 0;
  touch-action: none; /* important for pointer panning on mobile */
}

.controls {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  padding: 0.5rem;
  background: #020712;
  flex-wrap: wrap;
}

.controls button,
.controls select,
.controls input {
  background: #111;
  color: #fff;
  border: 1px solid #333;
  padding: 0.4rem 0.6rem;
}

.controls label {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 0.8rem;
  color: #cfd6e5;
}

.controls label span {
  font-size: 0.7rem;
  color: #7a8696;
}

.controls input[type="range"] {
  min-width: 160px;
}
</style>
HTML;

ob_start();
?>
<section class="player">
  <div class="player-shell" id="playerShell">

    <div class="viewer">
      <video id="video"
             muted
             playsinline
             preload="auto"
             crossorigin="anonymous">
        <source src="/videos/matches/match_1/source/veo/panoramic/match_1_panoramic.mp4" type="video/mp4">
      </video>

      <div id="threeMount"></div>
    </div>

    <div class="controls">
      <button id="playBtn" type="button">▶︎</button>
      <button id="pauseBtn" type="button">⏸</button>
      <input id="seek" type="range" min="0" max="100" value="0" step="0.1" aria-label="Seek">
      <input id="volume" type="range" min="0" max="1" step="0.05" value="0.8" aria-label="Volume">
      <select id="speed" aria-label="Speed">
        <option value="0.5">0.5x</option>
        <option value="1" selected>1x</option>
        <option value="1.5">1.5x</option>
        <option value="2">2x</option>
      </select>
      <button id="fsBtn" type="button">⛶</button>
    </div>

    <div class="controls">
      <label>
        Stack
        <select id="stackSelect" aria-label="Stack select">
          <option value="0">Top</option>
          <option value="1" selected>Bottom</option>
        </select>
      </label>
      <label>
        Seam offset
        <input id="seamOffset" type="range" min="-0.5" max="0.5" step="0.001" value="0" aria-label="Seam offset">
        <span class="slider-value" id="seamOffsetValue">0.000</span>
      </label>
      <label>
        Curve strength
        <input id="curveStrength" type="range" min="0" max="1" step="0.01" value="0.35" aria-label="Curve strength">
        <span class="slider-value" id="curveStrengthValue">0.35</span>
      </label>
      <label>
        Vertical warp
        <input id="verticalWarp" type="range" min="0" max="1" step="0.01" value="0.15" aria-label="Vertical warp">
        <span class="slider-value" id="verticalWarpValue">0.15</span>
      </label>
    </div>

  </div>
</section>
<?php
$content = ob_get_clean();

$footerScripts = <<<'HTML'
<!-- ✅ WORKING THREE.JS (r79) -->
<script src="https://cdn.jsdelivr.net/npm/three-js@79.0.0/three.min.js"></script>

<script>
(() => {
  const video = document.getElementById('video');
  const mount = document.getElementById('threeMount');
  const shell = document.getElementById('playerShell');
  const debug = false;

  if (typeof THREE === 'undefined') {
    console.error('RENDERER FAILED: THREE is not defined');
    return;
  }

  // r79: BufferGeometry uses addAttribute (not setAttribute)
  function createPanoramaGeometry(radius = 5, height = 5, horizontalSegments = 256, verticalSegments = 64) {
    const positions = [];
    const normals = [];
    const uvs = [];
    const indices = [];

    for (let y = 0; y <= verticalSegments; y++) {
      const t = y / verticalSegments;
      const posY = height * (t - 0.5);

      for (let x = 0; x <= horizontalSegments; x++) {
        const u = x / horizontalSegments;

        // 180° span: [-pi/2, +pi/2]
        const theta = -Math.PI / 2 + u * Math.PI;
        const s = Math.sin(theta);
        const c = Math.cos(theta);

        // cylinder-ish surface (we'll warp in shader for "projection curve")
        positions.push(radius * s, posY, radius * c);

        // outward normal (BackSide material means we see inside)
        normals.push(s, 0, c);

        // base UVs: u in [0..1], v in [0..1]
        uvs.push(u, t);
      }
    }

    for (let y = 0; y < verticalSegments; y++) {
      for (let x = 0; x < horizontalSegments; x++) {
        const row = horizontalSegments + 1;
        const base = y * row + x;
        const a = base;
        const b = base + row;
        const c = b + 1;
        const d = a + 1;
        indices.push(a, b, d, b, c, d);
      }
    }

    const indexType = (indices.length > 65535) ? Uint32Array : Uint16Array;

    const geometry = new THREE.BufferGeometry();
    geometry.addAttribute('position', new THREE.BufferAttribute(new Float32Array(positions), 3));
    geometry.addAttribute('normal', new THREE.BufferAttribute(new Float32Array(normals), 3));
    geometry.addAttribute('uv', new THREE.BufferAttribute(new Float32Array(uvs), 2));

    // r79 supports setIndex; pass either array or BufferAttribute. We'll use BufferAttribute.
    geometry.setIndex(new THREE.BufferAttribute(new indexType(indices), 1));

    geometry.computeBoundingSphere();
    return geometry;
  }

  // --- Renderer / Scene / Camera ---
  const renderer = new THREE.WebGLRenderer({ antialias: true });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  mount.appendChild(renderer.domElement);

  const scene = new THREE.Scene();

  const camera = new THREE.PerspectiveCamera(
    65,
    Math.max(1, mount.clientWidth) / Math.max(1, mount.clientHeight),
    0.1,
    100
  );
  camera.position.set(0, 0, 0);
  camera.lookAt(0, 0, 1);

  // --- Custom geometry (replaces CylinderGeometry) ---
  const geometry = createPanoramaGeometry(5, 5, 256, 64);

  // --- Shader (stacked UV + seam offset + "projection curve") ---
  const uniforms = {
    uTexture: { value: null },
    seamOffset: { value: 0.0 },
    stackSelect: { value: 1.0 }, // 0=top, 1=bottom
    curveStrength: { value: 0.35 },
    verticalWarp: { value: 0.15 }
  };

  const shaderMaterial = new THREE.ShaderMaterial({
    uniforms: uniforms,
    side: THREE.BackSide,
    vertexShader: `
      varying vec2 vUv;
      uniform float curveStrength;
      uniform float verticalWarp;

      void main() {
        vUv = uv;

        // "Match Veo projection curve" approximation:
        // warp the surface slightly based on horizontal UV (u).
        // Keep cheap: no heavy ops beyond sin.
        const float PI = 3.141592653589793;
        float u = vUv.x;

        // peak in middle, less near edges
        float curve = sin(u * PI) * curveStrength;

        vec3 pos = position;

        // curve the surface (forward/back) and squeeze X a bit
        pos.z += curve * 0.45;
        pos.x *= 1.0 - curve * 0.25;

        // gentle vertical warp
        pos.y += (vUv.y - 0.5) * verticalWarp;

        gl_Position = projectionMatrix * modelViewMatrix * vec4(pos, 1.0);
      }
    `,
    fragmentShader: `
      uniform sampler2D uTexture;
      uniform float seamOffset;
      uniform float stackSelect;
      varying vec2 vUv;

      void main() {
        vec2 uv = vUv;

        // Fix seam / wrap horizontally
        uv.x = fract(uv.x + seamOffset + 1.0);

        // stacked UV correction (top/bottom halves)
        uv.y = uv.y * 0.5 + stackSelect * 0.5;

        // inverted Y for Veo encoding
        uv.y = 1.0 - uv.y;

        gl_FragColor = texture2D(uTexture, uv);
      }
    `
  });
  shaderMaterial.needsUpdate = true;
  console.log('UV test', geometry.attributes.uv.array.slice(0, 8));

  const debugMaterial = new THREE.MeshBasicMaterial({ color: 0x00ff00, side: THREE.BackSide });

  const mesh = new THREE.Mesh(geometry, shaderMaterial);
  mesh.rotation.y = Math.PI;
  // NOTE: Keep orientation consistent with your earlier attempts
  scene.add(mesh);

  document.addEventListener('keydown', (event) => {
    if (!event.key) return;
    if (event.key.toLowerCase() !== 'd') return;
    const isShader = mesh.material === shaderMaterial;
    mesh.material = isShader ? debugMaterial : shaderMaterial;
    console.log('Material toggle:', mesh.material === shaderMaterial ? 'ShaderMaterial' : 'MeshBasicMaterial');
  });

  // --- VideoTexture (r79) ---
  let videoTexture = null;

  function bindVideoTexture() {
    if (videoTexture) return;
    if (video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) return;

    videoTexture = new THREE.VideoTexture(video);
    videoTexture.minFilter = THREE.LinearFilter;
    videoTexture.magFilter = THREE.LinearFilter;
    videoTexture.format = THREE.RGBFormat;
    videoTexture.encoding = THREE.sRGBEncoding;
    videoTexture.needsUpdate = true;

    uniforms.uTexture.value = videoTexture;
    console.log('VideoTexture bound:', video.videoWidth, video.videoHeight);
  }

  video.addEventListener('canplay', bindVideoTexture);
  video.addEventListener('playing', bindVideoTexture);

  function resizeRenderer() {
    const width = Math.max(1, mount.clientWidth);
    const height = Math.max(1, mount.clientHeight);
    renderer.setSize(width, height, false);
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
  }
  resizeRenderer();
  window.addEventListener('resize', resizeRenderer);
  document.addEventListener('fullscreenchange', resizeRenderer);

  // --- Inertia panning ---
  const HORIZONTAL_LIMIT = (Math.PI / 2) - 0.05; // 180° content guardrails
  const DRAG_SPEED = 0.0048;
  const FRICTION = 0.92;
  const MIN_VELOCITY = 0.0005;

  function clamp(v, min, max) { return Math.max(min, Math.min(max, v)); }

  let yaw = 0;
  let yawVelocity = 0;
  let dragging = false;
  let lastPointerX = 0;
  let lastMoveTime = 0;

  function applyYaw(delta) {
    yaw = clamp(yaw + delta, -HORIZONTAL_LIMIT, HORIZONTAL_LIMIT);
    mesh.rotation.y = yaw;
  }

  mount.addEventListener('pointerdown', (event) => {
    dragging = true;
    yawVelocity = 0;
    lastPointerX = event.clientX;
    lastMoveTime = event.timeStamp || performance.now();
    if (mount.setPointerCapture) {
      mount.setPointerCapture(event.pointerId);
    }
  });

  mount.addEventListener('pointermove', (event) => {
    if (!dragging) return;
    const now = event.timeStamp || performance.now();
    const dt = Math.max((now - lastMoveTime) / 1000, 0.001);
    const dx = event.clientX - lastPointerX;
    lastPointerX = event.clientX;
    lastMoveTime = now;

    const deltaYaw = -dx * DRAG_SPEED;
    const prevYaw = yaw;

    applyYaw(deltaYaw);

    // velocity (radians/sec)
    yawVelocity = (yaw - prevYaw) / dt;
  });

  function releasePointer(event) {
    if (!dragging) return;
    dragging = false;

    if (event && event.pointerId != null && mount.releasePointerCapture) {
      try { mount.releasePointerCapture(event.pointerId); } catch (e) {}
    }

    if (Math.abs(yawVelocity) < MIN_VELOCITY) yawVelocity = 0;
  }

  mount.addEventListener('pointerup', releasePointer);
  mount.addEventListener('pointerleave', releasePointer);
  mount.addEventListener('pointercancel', releasePointer);

  let lastFrame = performance.now();
  function animate() {
    requestAnimationFrame(animate);

    const now = performance.now();
    const dt = Math.min((now - lastFrame) / 1000, 0.03);
    lastFrame = now;

    if (!dragging && yawVelocity !== 0) {
      yaw += yawVelocity * dt;

      if (yaw < -HORIZONTAL_LIMIT || yaw > HORIZONTAL_LIMIT) {
        yaw = clamp(yaw, -HORIZONTAL_LIMIT, HORIZONTAL_LIMIT);
        yawVelocity = 0;
      } else {
        yawVelocity *= Math.pow(FRICTION, dt * 60);
        if (Math.abs(yawVelocity) < MIN_VELOCITY) yawVelocity = 0;
      }
      mesh.rotation.y = yaw;
    }

    if (videoTexture) {
      videoTexture.needsUpdate = true;
    }

    renderer.render(scene, camera);
  }
  animate();

  // --- UI bindings ---
  const stackSelect = document.getElementById('stackSelect');
  const seamSlider = document.getElementById('seamOffset');
  const seamValue = document.getElementById('seamOffsetValue');
  const curveSlider = document.getElementById('curveStrength');
  const curveValue = document.getElementById('curveStrengthValue');
  const warpSlider = document.getElementById('verticalWarp');
  const warpValue = document.getElementById('verticalWarpValue');

  function updateStack() {
    const v = parseFloat(stackSelect.value);
    uniforms.stackSelect.value = (Number.isFinite(v) ? v : 0);
  }
  stackSelect.addEventListener('change', updateStack);
  updateStack();

  function updateSeam() {
    const v = parseFloat(seamSlider.value);
    const val = Number.isFinite(v) ? v : 0;
    uniforms.seamOffset.value = val;
    seamValue.textContent = val.toFixed(3);
  }
  seamSlider.addEventListener('input', updateSeam);
  updateSeam();

  function updateCurve() {
    const v = parseFloat(curveSlider.value);
    const val = Number.isFinite(v) ? v : 0;
    uniforms.curveStrength.value = val;
    curveValue.textContent = val.toFixed(2);
  }
  curveSlider.addEventListener('input', updateCurve);
  updateCurve();

  function updateWarp() {
    const v = parseFloat(warpSlider.value);
    const val = Number.isFinite(v) ? v : 0;
    uniforms.verticalWarp.value = val;
    warpValue.textContent = val.toFixed(2);
  }
  warpSlider.addEventListener('input', updateWarp);
  updateWarp();

  // --- Playback controls ---
  document.getElementById('playBtn').onclick = async () => {
    try {
      // For autoplay policies, muted play first helps on many browsers
      const wasMuted = video.muted;
      video.muted = true;
      await video.play();
      video.muted = wasMuted;
    } catch (e) {
      console.error('Play failed:', e);
    }
  };

  document.getElementById('pauseBtn').onclick = () => video.pause();

  document.getElementById('volume').oninput = (e) => {
    const v = parseFloat(e.target.value);
    video.volume = Number.isFinite(v) ? v : 0.8;
    video.muted = (video.volume === 0);
  };

  document.getElementById('speed').onchange = (e) => {
    const v = parseFloat(e.target.value);
    video.playbackRate = Number.isFinite(v) ? v : 1;
  };

  document.getElementById('seek').oninput = (e) => {
    if (video.duration && Number.isFinite(video.duration)) {
      const ratio = (parseFloat(e.target.value) || 0) / 100;
      video.currentTime = ratio * video.duration;
    }
  };

  video.addEventListener('timeupdate', () => {
    if (video.duration && Number.isFinite(video.duration)) {
      document.getElementById('seek').value = (video.currentTime / video.duration) * 100;
    }
  });

  // Fullscreen
  document.getElementById('fsBtn').onclick = () => {
    const target = shell || mount;
    if (document.fullscreenElement) {
      document.exitFullscreen();
      return;
    }
    if (target.requestFullscreen) {
      target.requestFullscreen();
    }
  };

})();
</script>
HTML;

require __DIR__ . '/../app/views/layout.php';
