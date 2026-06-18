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
    .admin-sidebar { width: 260px; background: var(--bg-card); border-right: 1px solid var(--border); padding: 24px; flex-shrink: 0; }
    .admin-sidebar h2 { font-size: 1.2rem; margin-bottom: 24px; }
    .admin-sidebar a { display: block; padding: 12px 16px; color: var(--text-muted); text-decoration: none; border-radius: 12px; margin-bottom: 4px; transition: all 0.2s; }
    .admin-sidebar a:hover, .admin-sidebar a.active { background: var(--bg-card-hover); color: var(--text); }
    .admin-main { flex: 1; padding: 40px; min-width: 0; }
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
      <a href="pedidos.php">Pedidos</a>
      <a href="usuarios.php">Usuarios</a>
      <a href="chatbot.php">Chatbot</a>
      <a href="configuracion.php">Configuración</a>
      <a href="../auth/logout.php">Cerrar Sesión</a>
      <a href="../index.php" style="margin-top:20px;color:var(--accent);">← Tienda</a>
    </aside>
    <main class="admin-main">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <h1 style="margin-bottom:0;">Dashboard</h1>
        <a href="../index.php" class="btn-primary" style="text-decoration:none;background:transparent;border:1px solid var(--border);font-size:0.9rem;padding:10px 20px;" target="_blank">Ver Tienda</a>
      </div>
      <?php
        $usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        $productos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
        $mensajes = $pdo->query("SELECT COUNT(*) FROM chatbot_logs")->fetchColumn();
        $pedidos = $pdo->query("SELECT * FROM pedidos ORDER BY id DESC")->fetchAll();
        $totalVentas = array_sum(array_map(fn($p) => floatval($p['total']), $pedidos));
        $pedidosPendientes = count(array_filter($pedidos, fn($p) => $p['estado'] === 'pendiente'));
        $pedidosConfirmados = count(array_filter($pedidos, fn($p) => $p['estado'] === 'confirmado'));
        $productosBajos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
        $prods = $pdo->query("SELECT * FROM productos")->fetchAll();
        $stockBajo = count(array_filter($prods, fn($p) => ($p['stock'] ?? 0) <= 5));
      ?>
      <div class="stats" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
        <div class="stat-card"><div class="num"><?= count($pedidos) ?></div><div class="label">Pedidos Totales</div></div>
        <div class="stat-card"><div class="num" style="color:var(--accent);"><?= $pedidosPendientes ?></div><div class="label">Pendientes</div></div>
        <div class="stat-card"><div class="num" style="color:var(--success);"><?= $pedidosConfirmados ?></div><div class="label">Confirmados</div></div>
        <div class="stat-card"><div class="num" style="color:var(--primary);">$<?= number_format($totalVentas, 0, ',', '.') ?></div><div class="label">Ventas Totales</div></div>
        <div class="stat-card"><div class="num"><?= $productos ?></div><div class="label">Productos</div></div>
        <div class="stat-card"><div class="num" style="color:<?= $stockBajo > 0 ? 'var(--accent)' : 'var(--success)' ?>"><?= $stockBajo ?></div><div class="label">Stock Bajo</div></div>
        <div class="stat-card"><div class="num"><?= $usuarios ?></div><div class="label">Usuarios</div></div>
        <div class="stat-card"><div class="num"><?= $mensajes ?></div><div class="label">Mensajes Chat</div></div>
      </div>

      <?php if (!empty($pedidos)): ?>
      <h2 style="margin-bottom:16px;margin-top:32px;">Últimos Pedidos</h2>
      <table>
        <tr><th>#</th><th>Cliente</th><th>Total</th><th>Pago</th><th>Envío</th><th>Estado</th></tr>
        <?php foreach (array_slice($pedidos, 0, 8) as $p):
          $estadoColors = ['pendiente' => '#fdcb6e', 'confirmado' => '#6c5ce7', 'enviado' => '#00b894', 'entregado' => '#00cec9', 'cancelado' => '#e17055'];
          $estados = ['pendiente' => 'Pendiente', 'confirmado' => 'Confirmado', 'enviado' => 'Enviado', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];
        ?>
        <tr>
          <td><?= $p['id'] ?></td>
          <td><?= htmlspecialchars($p['nombre']) ?></td>
          <td>$<?= number_format(floatval($p['total']), 0, ',', '.') ?></td>
          <td style="font-size:0.85rem;"><?= $p['metodo_pago'] ?></td>
          <td style="font-size:0.85rem;"><?= $p['tipo_envio'] ?? 'domicilio' ?></td>
          <td><span class="badge" style="background:<?= $estadoColors[$p['estado']] ?? '#888' ?>33;color:<?= $estadoColors[$p['estado']] ?? '#888' ?>"><?= $estados[$p['estado']] ?? $p['estado'] ?></span></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <a href="pedidos.php" style="display:inline-block;margin-top:16px;color:var(--primary);text-decoration:none;font-weight:600;">Ver todos los pedidos →</a>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
