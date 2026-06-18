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
        $stmt = $pdo->prepare("INSERT INTO productos (nombre, categoria, precio, color) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['nombre'], $_POST['categoria'], $_POST['precio'], $_POST['color']]);
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $pdo->prepare("DELETE FROM productos WHERE id = ?")->execute([$_POST['id']]);
    }
    header('Location: productos.php');
    exit;
}

$productos = $pdo->query("SELECT * FROM productos ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Productos - Admin ShopRive</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .admin-layout { display: flex; min-height: 100vh; }
    .admin-sidebar { width: 260px; background: var(--bg-card); border-right: 1px solid var(--border); padding: 24px; }
    .admin-sidebar h2 { font-size: 1.2rem; margin-bottom: 24px; }
    .admin-sidebar a { display: block; padding: 12px 16px; color: var(--text-muted); text-decoration: none; border-radius: 12px; margin-bottom: 4px; }
    .admin-sidebar a:hover, .admin-sidebar a.active { background: var(--bg-card-hover); color: var(--text); }
    .admin-main { flex: 1; padding: 40px; }
    .admin-main h1 { font-size: 2rem; margin-bottom: 32px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
    th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border); }
    th { color: var(--text-muted); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; }
    .color-dot { display: inline-block; width: 16px; height: 16px; border-radius: 50%; vertical-align: middle; }
    .form-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 32px; max-width: 500px; }
    .form-card h2 { margin-bottom: 20px; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted); }
    .form-group input, .form-group select { width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px; color: var(--text); outline: none; }
    .form-group input:focus { border-color: var(--primary); }
    .btn-delete { background: transparent; border: 1px solid var(--accent); color: var(--accent); padding: 6px 14px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; }
    .btn-delete:hover { background: var(--accent); color: white; }
  </style>
</head>
<body>
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <h2>ShopRive Admin</h2>
      <a href="index.php">Dashboard</a>
      <a href="productos.php" class="active">Productos</a>
      <a href="../auth/logout.php">Cerrar Sesión</a>
      <a href="../index.php" style="margin-top:20px;color:var(--accent);">← Tienda</a>
    </aside>
    <main class="admin-main">
      <h1>Productos</h1>
      <table>
        <tr><th>ID</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Color</th><th>Acción</th></tr>
        <?php foreach ($productos as $p): ?>
        <tr>
          <td><?= $p['id'] ?></td>
          <td><?= htmlspecialchars($p['nombre']) ?></td>
          <td><?= $p['categoria'] ?></td>
          <td>$<?= number_format($p['precio'], 0, ',', '.') ?></td>
          <td><span class="color-dot" style="background:<?= $p['color'] ?>"></span> <?= $p['color'] ?></td>
          <td>
            <form method="POST" onsubmit="return confirm('¿Eliminar?')">
              <input type="hidden" name="id" value="<?= $p['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <button class="btn-delete">Eliminar</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
      <div class="form-card">
        <h2>Agregar Producto</h2>
        <form method="POST">
          <input type="hidden" name="action" value="add">
          <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="nombre" required>
          </div>
          <div class="form-group">
            <label>Categoría</label>
            <select name="categoria">
              <option value="electronica">Electrónica</option>
              <option value="moda">Moda</option>
              <option value="hogar">Hogar</option>
              <option value="deportes">Deportes</option>
            </select>
          </div>
          <div class="form-group">
            <label>Precio</label>
            <input type="number" name="precio" required>
          </div>
          <div class="form-group">
            <label>Color</label>
            <input type="color" name="color" value="#6c5ce7">
          </div>
          <button class="btn-primary" type="submit">Agregar</button>
        </form>
      </div>
    </main>
  </div>
</body>
</html>
