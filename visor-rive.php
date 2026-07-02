<?php
header('Content-Type: text/html; charset=utf-8');
$rivDir = __DIR__ . '/assets/riv';
$files = glob($rivDir . '/*.riv');
$riveFiles = [];
foreach ($files as $f) {
    $riveFiles[] = [
        'name' => basename($f),
        'path' => 'assets/riv/' . basename($f),
        'size' => filesize($f),
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Visor .riv - Rive Animations</title>
<script src="https://unpkg.com/@rive-app/webgl@2.9.1"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: system-ui, -apple-system, sans-serif;
  background: #111; color: #eee;
  min-height: 100vh;
  display: flex; flex-direction: column;
}
header {
  background: #1a1a2e;
  padding: 16px 24px;
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 12px;
  border-bottom: 1px solid #333;
}
header h1 { font-size: 20px; font-weight: 600; color: #fff; }
header h1 span { color: #6c5ce7; }
header label {
  background: #2d2d44; color: #ccc; padding: 8px 16px;
  border-radius: 8px; cursor: pointer; font-size: 14px;
  transition: background .2s; display: inline-flex; align-items: center; gap: 8px;
}
header label:hover { background: #3d3d5c; }
header input[type="file"] { display: none; }
.container { display: flex; flex: 1; overflow: hidden; }
.sidebar {
  width: 260px; min-width: 260px;
  background: #1a1a2e;
  border-right: 1px solid #333;
  overflow-y: auto; padding: 12px;
  display: flex; flex-direction: column; gap: 4px;
}
.sidebar .title {
  font-size: 12px; text-transform: uppercase; letter-spacing: 1px;
  color: #666; padding: 8px 8px 4px; font-weight: 600;
}
.file-item {
  padding: 10px 12px; border-radius: 8px; cursor: pointer;
  font-size: 14px; transition: all .15s; color: #aaa;
  display: flex; align-items: center; gap: 10px;
}
.file-item:hover { background: #2d2d44; color: #fff; }
.file-item.active { background: #6c5ce7; color: #fff; font-weight: 500; }
.file-item .icon { font-size: 18px; opacity: .7; }
.file-item.active .icon { opacity: 1; }
.main {
  flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 24px; position: relative;
}
.main .placeholder {
  text-align: center; color: #555;
}
.main .placeholder .big-icon { font-size: 64px; margin-bottom: 16px; }
.main .placeholder h2 { font-size: 18px; margin-bottom: 8px; color: #777; }
.main .placeholder p { font-size: 14px; }
#viewer-canvas {
  max-width: 100%; max-height: 100%;
  border-radius: 12px;
  display: none;
}
.controls {
  display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;
  margin-top: 16px; padding: 12px;
}
.controls button {
  background: #2d2d44; color: #ccc; border: none; padding: 8px 16px;
  border-radius: 6px; cursor: pointer; font-size: 13px; transition: background .2s;
}
.controls button:hover { background: #3d3d5c; color: #fff; }
.controls button.active { background: #6c5ce7; color: #fff; }
.info-bar {
  background: #1a1a2e; border-top: 1px solid #333;
  padding: 10px 24px; font-size: 13px; color: #666;
  display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px;
}
@media (max-width: 768px) {
  .container { flex-direction: column; }
  .sidebar { width: 100%; min-width: 0; max-height: 200px; border-right: none; border-bottom: 1px solid #333; }
}
</style>
</head>
<body>

<header>
  <h1><span>Rive</span> Viewer</h1>
  <label>
    <span>📁</span> Cargar .riv externo
    <input type="file" accept=".riv" id="fileInput">
  </label>
</header>

<div class="container">
  <aside class="sidebar" id="fileList">
    <div class="title">Archivos .riv (<?= count($riveFiles) ?>)</div>
    <?php foreach ($riveFiles as $f): ?>
    <div class="file-item" data-path="<?= htmlspecialchars($f['path']) ?>">
      <span class="icon">🎬</span>
      <span><?= htmlspecialchars($f['name']) ?></span>
    </div>
    <?php endforeach; ?>
  </aside>
  <div class="main" id="mainArea">
    <div class="placeholder" id="placeholder">
      <div class="big-icon">🎬</div>
      <h2>Seleccioná un archivo .riv</h2>
      <p>Hacé clic en un archivo de la lista o subí uno propio</p>
    </div>
    <canvas id="viewer-canvas" width="800" height="600"></canvas>
    <div class="controls" id="controls" style="display:none;">
      <button id="btnPlay" class="active" data-mode="play">▶ Reproducir</button>
      <button data-mode="pause">⏸ Pausar</button>
      <button data-mode="reset">⏹ Reset</button>
      <span style="width:1px;background:#333;margin:0 4px;"></span>
      <button class="active" data-fit="cover">Cubrir</button>
      <button data-fit="contain">Contener</button>
      <button data-fit="fill">Llenar</button>
      <button data-fit="fitWidth">Ancho</button>
      <button data-fit="fitHeight">Alto</button>
      <span style="width:1px;background:#333;margin:0 4px;"></span>
      <button data-layout="default">Original</button>
      <button data-layout="flipX">Voltear X</button>
      <button data-layout="flipY">Voltear Y</button>
    </div>
  </div>
</div>

<div class="info-bar" id="infoBar">
  <span id="fileInfo">Sin archivo seleccionado</span>
  <span id="animInfo"></span>
</div>

<script>
let currentRive = null;
let currentPath = null;
let isPlaying = true;
let currentFit = 'cover';
let currentLayout = 'default';

const canvas = document.getElementById('viewer-canvas');
const placeholder = document.getElementById('placeholder');
const controls = document.getElementById('controls');
const fileInfo = document.getElementById('fileInfo');
const animInfo = document.getElementById('animInfo');

function loadRiveFile(src, name) {
  if (currentRive) {
    try { currentRive.cleanup(); } catch(e) {}
    currentRive = null;
  }
  canvas.style.display = 'block';
  placeholder.style.display = 'none';
  controls.style.display = 'flex';
  fileInfo.textContent = name || src;

  let layout = new rive.Layout({
    fit: rive.Fit[currentFit.charAt(0).toUpperCase() + currentFit.slice(1)] || rive.Fit.Cover,
    alignment: rive.Alignment.Center,
  });

  if (currentLayout === 'flipX') layout = rive.Layout.from({flipX: true, ...layout});
  if (currentLayout === 'flipY') layout = rive.Layout.from({flipY: true, ...layout});

  currentRive = new rive.Rive({
    src: src,
    canvas: canvas,
    autoplay: isPlaying,
    layout: layout,
    onLoad: () => {
      currentRive.resizeDrawingSurfaceToCanvas();
      const names = currentRive.animationNames();
      animInfo.textContent = 'Animaciones: ' + (names.length ? names.join(', ') : 'ninguna');
    },
    onPlay: () => { isPlaying = true; updatePlayBtn(); },
    onPause: () => { isPlaying = false; updatePlayBtn(); },
    onStop: () => { isPlaying = false; updatePlayBtn(); },
  });
}

function updatePlayBtn() {
  document.querySelectorAll('[data-mode]').forEach(b => b.classList.remove('active'));
  const mode = isPlaying ? 'play' : 'pause';
  const btn = document.querySelector(`[data-mode="${mode}"]`);
  if (btn) btn.classList.add('active');
}

// click en lista de archivos
document.querySelectorAll('.file-item').forEach(el => {
  el.addEventListener('click', () => {
    document.querySelectorAll('.file-item').forEach(e => e.classList.remove('active'));
    el.classList.add('active');
    const path = el.dataset.path;
    currentPath = path;
    loadRiveFile(path, el.querySelector('span:last-child').textContent);
  });
});

// file input externo
document.getElementById('fileInput').addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (!file) return;
  const url = URL.createObjectURL(file);
  document.querySelectorAll('.file-item').forEach(el => el.classList.remove('active'));
  loadRiveFile(url, '📁 ' + file.name);
});

// controles
document.querySelectorAll('[data-mode]').forEach(btn => {
  btn.addEventListener('click', () => {
    if (!currentRive) return;
    const mode = btn.dataset.mode;
    if (mode === 'play') { currentRive.play(); isPlaying = true; }
    else if (mode === 'pause') { currentRive.pause(); isPlaying = false; }
    else if (mode === 'reset') {
      currentRive.stop();
      currentRive.play();
      isPlaying = true;
    }
    document.querySelectorAll('[data-mode]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  });
});

document.querySelectorAll('[data-fit]').forEach(btn => {
  btn.addEventListener('click', () => {
    currentFit = btn.dataset.fit;
    document.querySelectorAll('[data-fit]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    if (currentPath) {
      const active = document.querySelector('.file-item.active');
      const name = active ? active.querySelector('span:last-child').textContent : currentPath;
      loadRiveFile(currentPath, name);
    }
  });
});

document.querySelectorAll('[data-layout]').forEach(btn => {
  btn.addEventListener('click', () => {
    currentLayout = btn.dataset.layout;
    document.querySelectorAll('[data-layout]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    if (currentPath) {
      const active = document.querySelector('.file-item.active');
      const name = active ? active.querySelector('span:last-child').textContent : currentPath;
      loadRiveFile(currentPath, name);
    }
  });
});
</script>
</body>
</html>
