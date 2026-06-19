<?php
require_once __DIR__ . '/../lib/SessionManager.php';
require_once __DIR__ . '/../lib/Logger.php';
Logger::init();
SessionManager::init();
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

// CRUD conocimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_conocimiento') {
        $stmt = $pdo->prepare("INSERT INTO chatbot_conocimiento (patron, respuesta) VALUES (?, ?)");
        $stmt->execute([$_POST['patron'], $_POST['respuesta']]);
    } elseif ($action === 'edit_conocimiento' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE chatbot_conocimiento SET patron=?, respuesta=? WHERE id=?");
        $stmt->execute([$_POST['patron'], $_POST['respuesta'], $_POST['id']]);
    } elseif ($action === 'delete_conocimiento' && isset($_POST['id'])) {
        $pdo->prepare("DELETE FROM chatbot_conocimiento WHERE id=?")->execute([$_POST['id']]);
    }
    header('Location: chatbot.php');
    exit;
}

$conocimiento = $pdo->query("SELECT * FROM chatbot_conocimiento ORDER BY id")->fetchAll();
$logs = $pdo->query("SELECT * FROM chatbot_logs ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chatbot - Admin ShopRive</title>
  <link rel="stylesheet" href="../css/style.css">
  <?php require_once __DIR__ . '/../config/apariencia.php'; renderThemeStyles(); ?>
  <style>
    .admin-layout { display: flex; min-height: 100vh; }
    .admin-sidebar { width: 260px; background: var(--bg-card); border-right: 1px solid var(--border); padding: 24px; flex-shrink: 0; }
    .admin-sidebar h2 { font-size: 1.2rem; margin-bottom: 24px; }
    .admin-sidebar a { display: block; padding: 12px 16px; color: var(--text-muted); text-decoration: none; border-radius: 12px; margin-bottom: 4px; }
    .admin-sidebar a:hover, .admin-sidebar a.active { background: var(--bg-card-hover); color: var(--text); }
    .admin-main { flex: 1; padding: 40px; min-width: 0; }
    .admin-main h1 { font-size: 2rem; margin-bottom: 24px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border); }
    th { color: var(--text-muted); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .btn-sm { padding: 6px 14px; border-radius: 8px; font-size: 0.85rem; cursor: pointer; border: none; font-weight: 600; }
    .btn-edit { background: rgba(108,92,231,0.15); color: var(--primary); border: 1px solid var(--primary); }
    .btn-edit:hover { background: var(--primary); color: white; }
    .btn-delete { background: transparent; border: 1px solid var(--accent); color: var(--accent); }
    .btn-delete:hover { background: var(--accent); color: white; }
    .actions { display: flex; gap: 6px; }
    .tabs { display: flex; gap: 4px; margin-bottom: 24px; }
    .tab-btn { padding: 10px 20px; border-radius: 10px; border: none; background: var(--bg); color: var(--text-muted); cursor: pointer; font-weight: 600; font-size: 0.9rem; }
    .tab-btn.active { background: var(--primary); color: white; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .modal-overlay { display: none; position: fixed; inset:0; background: rgba(0,0,0,0.6); z-index:200; align-items:center; justify-content:center; }
    .modal-overlay.open { display: flex; }
    .modal-card { background: var(--bg-card); border:1px solid var(--border); border-radius:20px; padding:32px; width:90%; max-width:520px; }
    .modal-card h2 { margin-bottom: 20px; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted); }
    .form-group input, .form-group textarea { width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px; color: var(--text); outline: none; font-family: inherit; }
    .form-group input:focus, .form-group textarea:focus { border-color: var(--primary); }
    .form-group textarea { min-height: 80px; resize: vertical; }
    .patron-tag { display: inline-block; padding: 2px 8px; border-radius: 6px; background: rgba(108,92,231,0.15); color: var(--primary); font-size: 0.8rem; margin: 1px; }
    .log-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 12px; }
    .log-card .meta { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px; }
    .log-card .msg { margin-bottom: 4px; }
    .log-card .msg strong { color: var(--primary); }
    .log-card .resp { color: var(--success); }
    .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
    .empty-state svg { width: 48px; height: 48px; opacity: 0.3; margin-bottom: 12px; }
  </style>
</head>
<body>
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <h2>ShopRive Admin</h2>
      <a href="index.php">Dashboard</a>
      <a href="productos.php">Productos</a>
      <a href="categorias.php">Categorías</a>
      <a href="pedidos.php">Pedidos</a>
      <a href="usuarios.php">Usuarios</a>
      <a href="chatbot.php" class="active">Chatbot</a>
      <a href="configuracion.php">Configuración</a>
      <a href="../auth/logout.php">Cerrar Sesión</a>
      <a href="../index.php" style="margin-top:20px;color:var(--accent);">← Tienda</a>
    </aside>
    <main class="admin-main">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <h1>Chatbot</h1>
        <a href="../index.php" class="btn-primary" style="text-decoration:none;background:transparent;border:1px solid var(--border);font-size:0.9rem;padding:10px 20px;" target="_blank">Ver Tienda</a>
      </div>

      <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('conocimiento',this)">Base de Conocimiento</button>
        <button class="tab-btn" onclick="switchTab('logs',this)">Conversaciones (<?= count($logs) ?>)</button>
      </div>

      <!-- BASE DE CONOCIMIENTO -->
      <div class="tab-content active" id="tab-conocimiento">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
          <p style="color:var(--text-muted);">Patrones y respuestas que el chatbot usa para responder. Usá <code style="background:var(--bg);padding:2px 6px;border-radius:4px;">|</code> para separar palabras clave.</p>
          <button class="btn-primary" onclick="document.getElementById('add-modal').classList.add('open')">+ Nueva</button>
        </div>
        <?php if (empty($conocimiento)): ?>
          <div class="empty-state"><p>No hay entradas de conocimiento.</p></div>
        <?php else: ?>
        <table>
          <tr><th>ID</th><th>Patrones</th><th>Respuesta</th><th>Acción</th></tr>
          <?php foreach ($conocimiento as $c): ?>
          <tr>
            <td><?= $c['id'] ?></td>
            <td><?php foreach (explode('|', $c['patron']) as $p): ?><span class="patron-tag"><?= htmlspecialchars(trim($p)) ?></span><?php endforeach; ?></td>
            <td style="max-width:300px;white-space:pre-wrap;word-break:break-word;font-size:0.9rem;"><?= htmlspecialchars($c['respuesta']) ?></td>
            <td>
              <div class="actions">
                <button class="btn-sm btn-edit" onclick="editConocimiento(<?= htmlspecialchars(json_encode($c)) ?>)">Editar</button>
                <form method="POST" onsubmit="return confirm('¿Eliminar?')">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="action" value="delete_conocimiento">
                  <button class="btn-sm btn-delete">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
      </div>

      <!-- LOGS -->
      <div class="tab-content" id="tab-logs">
        <?php if (empty($logs)): ?>
          <div class="empty-state"><p>No hay conversaciones todavía.</p></div>
        <?php else: ?>
          <?php foreach ($logs as $l): ?>
          <div class="log-card">
            <div class="meta">#<?= $l['id'] ?> · <?= $l['created_at'] ?? '-' ?></div>
            <div class="msg"><strong>Cliente:</strong> <?= htmlspecialchars($l['mensaje'] ?? '') ?></div>
            <div class="msg resp"><strong>Bot:</strong> <?= htmlspecialchars($l['respuesta'] ?? '') ?></div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <!-- ADD MODAL -->
  <div class="modal-overlay" id="add-modal" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-card">
      <h2>Nueva Entrada</h2>
      <form method="POST">
        <input type="hidden" name="action" value="add_conocimiento">
        <div class="form-group">
          <label>Patrones (separados por |)</label>
          <input type="text" name="patron" required placeholder="ej: hola|buenas|que tal">
        </div>
        <div class="form-group">
          <label>Respuesta del bot</label>
          <textarea name="respuesta" required placeholder="¡Hola! ¿En qué puedo ayudarte?"></textarea>
        </div>
        <div style="display:flex;gap:12px;">
          <button class="btn-primary" type="submit">Agregar</button>
          <button class="btn-primary" type="button" onclick="document.getElementById('add-modal').classList.remove('open')" style="background:transparent;border:1px solid var(--border);color:var(--text-muted);">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT MODAL -->
  <div class="modal-overlay" id="edit-modal" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-card">
      <h2>Editar Entrada</h2>
      <form method="POST">
        <input type="hidden" name="action" value="edit_conocimiento">
        <input type="hidden" name="id" id="edit-id">
        <div class="form-group">
          <label>Patrones (separados por |)</label>
          <input type="text" name="patron" id="edit-patron" required>
        </div>
        <div class="form-group">
          <label>Respuesta del bot</label>
          <textarea name="respuesta" id="edit-respuesta" required></textarea>
        </div>
        <div style="display:flex;gap:12px;">
          <button class="btn-primary" type="submit">Guardar</button>
          <button class="btn-primary" type="button" onclick="document.getElementById('edit-modal').classList.remove('open')" style="background:transparent;border:1px solid var(--border);color:var(--text-muted);">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function switchTab(name, btn) {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('tab-' + name).classList.add('active');
    }
    function editConocimiento(c) {
      document.getElementById('edit-id').value = c.id;
      document.getElementById('edit-patron').value = c.patron;
      document.getElementById('edit-respuesta').value = c.respuesta;
      document.getElementById('edit-modal').classList.add('open');
    }
  </script>
</body>
</html>
