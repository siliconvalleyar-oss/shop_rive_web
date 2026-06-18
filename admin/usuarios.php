<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_rol') {
        $nuevoRol = $_POST['rol'] === 'admin' ? 'usuario' : 'admin';
        $pdo->prepare("UPDATE usuarios SET rol = ? WHERE id = ?")->execute([$nuevoRol, $_POST['id']]);
    } elseif ($action === 'delete' && $_POST['id'] != $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$_POST['id']]);
    }
    header('Location: usuarios.php');
    exit;
}

$usuarios = $pdo->query("SELECT id, nombre, email, rol, created_at FROM usuarios ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Usuarios - Admin ShopRive</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .admin-layout { display: flex; min-height: 100vh; }
    .admin-sidebar { width: 260px; background: var(--bg-card); border-right: 1px solid var(--border); padding: 24px; flex-shrink: 0; }
    .admin-sidebar h2 { font-size: 1.2rem; margin-bottom: 24px; }
    .admin-sidebar a { display: block; padding: 12px 16px; color: var(--text-muted); text-decoration: none; border-radius: 12px; margin-bottom: 4px; }
    .admin-sidebar a:hover, .admin-sidebar a.active { background: var(--bg-card-hover); color: var(--text); }
    .admin-main { flex: 1; padding: 40px; min-width: 0; }
    .admin-main h1 { font-size: 2rem; margin-bottom: 32px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border); }
    th { color: var(--text-muted); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge { display: inline-block; padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 600; }
    .badge-admin { background: rgba(108,92,231,0.2); color: var(--primary); }
    .badge-usuario { background: rgba(0,184,148,0.2); color: var(--success); }
    .actions { display: flex; gap: 6px; align-items: center; }
    .btn-sm { padding: 6px 14px; border-radius: 8px; font-size: 0.85rem; cursor: pointer; border: 1px solid var(--border); background: transparent; color: var(--text-muted); }
    .btn-sm:hover { border-color: var(--primary); color: var(--text); }
    .btn-danger { border-color: var(--accent); color: var(--accent); }
    .btn-danger:hover { background: var(--accent); color: white; }
    .you-badge { font-size: 0.75rem; color: var(--text-muted); margin-left: 6px; }
    .user-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 16px; margin-bottom: 12px; }
    .user-card .avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--accent)); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 700; flex-shrink: 0; }
    .user-card .info { flex: 1; }
    .user-card .info .name { font-weight: 600; }
    .user-card .info .email { font-size: 0.85rem; color: var(--text-muted); }
  </style>
</head>
<body>
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <h2>ShopRive Admin</h2>
      <a href="index.php">Dashboard</a>
      <a href="productos.php">Productos</a>
      <a href="pedidos.php">Pedidos</a>
      <a href="usuarios.php" class="active">Usuarios</a>
      <a href="chatbot.php">Chatbot</a>
      <a href="configuracion.php">Configuración</a>
      <a href="../auth/logout.php">Cerrar Sesión</a>
      <a href="../index.php" style="margin-top:20px;color:var(--accent);">← Tienda</a>
    </aside>
    <main class="admin-main">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <h1 style="margin-bottom:0;">Usuarios</h1>
        <a href="../index.php" class="btn-primary" style="text-decoration:none;background:transparent;border:1px solid var(--border);font-size:0.9rem;padding:10px 20px;" target="_blank">Ver Tienda</a>
      </div>
      <?php foreach ($usuarios as $u): ?>
      <div class="user-card">
        <div class="avatar"><?= strtoupper(substr($u['nombre'], 0, 1)) ?></div>
        <div class="info">
          <div class="name">
            <?= htmlspecialchars($u['nombre']) ?>
            <span class="badge badge-<?= $u['rol'] ?>"><?= $u['rol'] ?></span>
            <?php if ((int)$u['id'] === (int)$_SESSION['user_id']): ?>
              <span class="you-badge">(vos)</span>
            <?php endif; ?>
          </div>
          <div class="email"><?= htmlspecialchars($u['email']) ?> · Registrado <?= $u['created_at'] ?? '-' ?></div>
        </div>
        <div class="actions">
          <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
            <form method="POST">
              <input type="hidden" name="id" value="<?= $u['id'] ?>">
              <input type="hidden" name="action" value="toggle_rol">
              <input type="hidden" name="rol" value="<?= $u['rol'] ?>">
              <button class="btn-sm">Cambiar a <?= $u['rol'] === 'admin' ? 'usuario' : 'admin' ?></button>
            </form>
            <form method="POST" onsubmit="return confirm('¿Eliminar usuario <?= htmlspecialchars($u['nombre']) ?>?')">
              <input type="hidden" name="id" value="<?= $u['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <button class="btn-sm btn-danger">Eliminar</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </main>
  </div>
</body>
</html>
