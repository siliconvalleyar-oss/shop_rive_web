<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower(str_replace(' ', '-', $_POST['nombre'])));
        $stmt = $pdo->prepare("INSERT INTO categorias (nombre, slug, riv_file) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['nombre'], $slug, $_POST['riv_file'] ?: 'hero-ui-animation']);
    } elseif ($action === 'edit' && isset($_POST['id'])) {
        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower(str_replace(' ', '-', $_POST['nombre'])));
        $stmt = $pdo->prepare("UPDATE categorias SET nombre=?, slug=?, riv_file=? WHERE id=?");
        $stmt->execute([$_POST['nombre'], $slug, $_POST['riv_file'] ?: 'hero-ui-animation', $_POST['id']]);
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $pdo->prepare("DELETE FROM categorias WHERE id = ?")->execute([$_POST['id']]);
    }
    header('Location: categorias.php');
    exit;
}

$categorias = $pdo->query("SELECT * FROM categorias ORDER BY id")->fetchAll();
if (empty($categorias)) {
    // Seed defaults
    $pdo->exec("CREATE TABLE categorias");
    $defaults = [['Electrónica', 'electronica', 'hero-ui-animation'], ['Moda', 'moda', 'shoe-showcase'], ['Hogar', 'hogar', 'rotating-can'], ['Deportes', 'deportes', 'off_road_car_0_6']];
    $stmt = $pdo->prepare("INSERT INTO categorias (nombre, slug, riv_file) VALUES (?, ?, ?)");
    foreach ($defaults as $d) $stmt->execute($d);
    $categorias = $pdo->query("SELECT * FROM categorias ORDER BY id")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Categorías - Admin ShopRive</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .admin-layout { display: flex; min-height: 100vh; }
    .admin-sidebar { width: 260px; background: var(--bg-card); border-right: 1px solid var(--border); padding: 24px; flex-shrink: 0; }
    .admin-sidebar h2 { font-size: 1.2rem; margin-bottom: 24px; }
    .admin-sidebar a { display: block; padding: 12px 16px; color: var(--text-muted); text-decoration: none; border-radius: 12px; margin-bottom: 4px; }
    .admin-sidebar a:hover, .admin-sidebar a.active { background: var(--bg-card-hover); color: var(--text); }
    .admin-main { flex: 1; padding: 40px; min-width: 0; }
    .admin-main h1 { font-size: 2rem; margin-bottom: 32px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
    th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border); }
    th { color: var(--text-muted); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 32px; max-width: 500px; }
    .form-card h2 { margin-bottom: 20px; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted); }
    .form-group input { width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px; color: var(--text); outline: none; }
    .form-group input:focus { border-color: var(--primary); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .btn-sm { padding: 6px 14px; border-radius: 8px; font-size: 0.85rem; cursor: pointer; border: none; font-weight: 600; }
    .btn-edit { background: rgba(108,92,231,0.15); color: var(--primary); border: 1px solid var(--primary); }
    .btn-edit:hover { background: var(--primary); color: white; }
    .btn-delete { background: transparent; border: 1px solid var(--accent); color: var(--accent); }
    .btn-delete:hover { background: var(--accent); color: white; }
    .actions { display: flex; gap: 6px; }
    .modal-overlay { display: none; position: fixed; inset:0; background: rgba(0,0,0,0.6); z-index:200; align-items:center; justify-content:center; }
    .modal-overlay.open { display: flex; }
    .modal-card { background: var(--bg-card); border:1px solid var(--border); border-radius:20px; padding:32px; width:90%; max-width:500px; }
  </style>
</head>
<body>
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <h2>ShopRive Admin</h2>
      <a href="index.php">Dashboard</a>
      <a href="productos.php">Productos</a>
      <a href="categorias.php" class="active">Categorías</a>
      <a href="pedidos.php">Pedidos</a>
      <a href="usuarios.php">Usuarios</a>
      <a href="chatbot.php">Chatbot</a>
      <a href="configuracion.php">Configuración</a>
      <a href="../auth/logout.php">Cerrar Sesión</a>
      <a href="../index.php" style="margin-top:20px;color:var(--accent);">← Tienda</a>
    </aside>
    <main class="admin-main">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <h1 style="margin-bottom:0;">Categorías</h1>
        <button class="btn-primary" onclick="document.getElementById('add-modal').classList.add('open')">+ Nueva</button>
      </div>
      <table>
        <tr><th>ID</th><th>Nombre</th><th>Slug</th><th>Archivo .riv</th><th>Acción</th></tr>
        <?php foreach ($categorias as $c): ?>
        <tr>
          <td><?= $c['id'] ?></td>
          <td><?= htmlspecialchars($c['nombre']) ?></td>
          <td><code style="color:var(--primary);"><?= htmlspecialchars($c['slug']) ?></code></td>
          <td style="font-size:0.85rem;color:var(--text-muted);"><?= htmlspecialchars($c['riv_file'] ?? '-') ?></td>
          <td>
            <div class="actions">
              <button class="btn-sm btn-edit" onclick="editCat(<?= htmlspecialchars(json_encode($c)) ?>)">Editar</button>
              <form method="POST" onsubmit="return confirm('¿Eliminar <?= htmlspecialchars($c['nombre']) ?>?')">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <input type="hidden" name="action" value="delete">
                <button class="btn-sm btn-delete">Eliminar</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    </main>
  </div>

  <div class="modal-overlay" id="add-modal" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-card">
      <h2>Nueva Categoría</h2>
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-group"><label>Nombre</label><input type="text" name="nombre" required placeholder="Ej: Juguetes"></div>
        <div class="form-group"><label>Archivo .riv</label><input type="text" name="riv_file" placeholder="ej: hero-ui-animation"></div>
        <div style="display:flex;gap:12px;">
          <button class="btn-primary" type="submit">Agregar</button>
          <button class="btn-primary" type="button" onclick="this.closest('.modal-overlay').classList.remove('open')" style="background:transparent;border:1px solid var(--border);color:var(--text-muted);">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="edit-modal" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-card">
      <h2>Editar Categoría</h2>
      <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit-id">
        <div class="form-group"><label>Nombre</label><input type="text" name="nombre" id="edit-nombre" required></div>
        <div class="form-group"><label>Archivo .riv</label><input type="text" name="riv_file" id="edit-riv" placeholder="ej: hero-ui-animation"></div>
        <div style="display:flex;gap:12px;">
          <button class="btn-primary" type="submit">Guardar</button>
          <button class="btn-primary" type="button" onclick="this.closest('.modal-overlay').classList.remove('open')" style="background:transparent;border:1px solid var(--border);color:var(--text-muted);">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function editCat(c) {
      document.getElementById('edit-id').value = c.id;
      document.getElementById('edit-nombre').value = c.nombre;
      document.getElementById('edit-riv').value = c.riv_file || '';
      document.getElementById('edit-modal').classList.add('open');
    }
  </script>
</body>
</html>
