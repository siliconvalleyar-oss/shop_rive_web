<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Admin - ShopRive</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .admin-layout { display: flex; min-height: 100vh; }
    .admin-sidebar { width: 260px; background: var(--bg-card); border-right: 1px solid var(--border); padding: 24px; }
    .admin-sidebar h2 { font-size: 1.2rem; margin-bottom: 24px; }
    .admin-sidebar a { display: block; padding: 12px 16px; color: var(--text-muted); text-decoration: none; border-radius: 12px; margin-bottom: 4px; transition: all 0.2s; }
    .admin-sidebar a:hover, .admin-sidebar a.active { background: var(--bg-card-hover); color: var(--text); }
    .admin-main { flex: 1; padding: 40px; }
    .admin-main h1 { font-size: 2rem; margin-bottom: 32px; }
    .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px; }
    .stat-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; }
    .stat-card .num { font-size: 2rem; font-weight: 700; color: var(--primary); }
    .stat-card .label { color: var(--text-muted); font-size: 0.9rem; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border); }
    th { color: var(--text-muted); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge { display: inline-block; padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 600; }
    .badge-admin { background: rgba(108,92,231,0.2); color: var(--primary); }
    .badge-user { background: rgba(0,184,148,0.2); color: var(--success); }
  </style>
</head>
<body>
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <h2>ShopRive Admin</h2>
      <a href="index.php" class="active">Dashboard</a>
      <a href="productos.php">Productos</a>
      <a href="../auth/logout.php">Cerrar Sesión</a>
      <a href="../index.php" style="margin-top:20px;color:var(--accent);">← Tienda</a>
    </aside>
    <main class="admin-main">
      <h1>Dashboard</h1>
      <?php
        $usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        $productos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
        $mensajes = $pdo->query("SELECT COUNT(*) FROM chatbot_logs")->fetchColumn();
        $users = $pdo->query("SELECT id, nombre, email, rol, created_at FROM usuarios ORDER BY id")->fetchAll();
      ?>
      <div class="stats">
        <div class="stat-card"><div class="num"><?= $usuarios ?></div><div class="label">Usuarios</div></div>
        <div class="stat-card"><div class="num"><?= $productos ?></div><div class="label">Productos</div></div>
        <div class="stat-card"><div class="num"><?= $mensajes ?></div><div class="label">Mensajes del Chat</div></div>
      </div>
      <h2 style="margin-bottom:16px;">Usuarios Registrados</h2>
      <table>
        <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Registro</th></tr>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><?= $u['id'] ?></td>
          <td><?= htmlspecialchars($u['nombre']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="badge badge-<?= $u['rol'] ?>"><?= $u['rol'] ?></span></td>
          <td><?= $u['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </main>
  </div>
</body>
</html>
