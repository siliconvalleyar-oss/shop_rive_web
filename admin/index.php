<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$productos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
$mensajes = $pdo->query("SELECT COUNT(*) FROM chatbot_logs")->fetchColumn();
$pedidos = $pdo->query("SELECT * FROM pedidos ORDER BY id DESC")->fetchAll();
$totalVentas = array_sum(array_map(fn($p) => floatval($p['total']), $pedidos));
$pedidosPendientes = count(array_filter($pedidos, fn($p) => $p['estado'] === 'pendiente'));
$pedidosConfirmados = count(array_filter($pedidos, fn($p) => $p['estado'] === 'confirmado'));
$prods = $pdo->query("SELECT * FROM productos")->fetchAll();
$stockBajo = count(array_filter($prods, fn($p) => ($p['stock'] ?? 0) <= 5));

// Desglose por tipo de envío
$envioDomicilio = array_filter($pedidos, fn($p) => ($p['tipo_envio'] ?? 'domicilio') === 'domicilio');
$envioRetiro = array_filter($pedidos, fn($p) => ($p['tipo_envio'] ?? '') === 'retiro');
$totalDomicilio = array_sum(array_map(fn($p) => floatval($p['total']), $envioDomicilio));
$totalRetiro = array_sum(array_map(fn($p) => floatval($p['total']), $envioRetiro));

// Desglose por método de pago
$metodos = ['tarjeta' => 'Tarjeta', 'qr' => 'Cuenta DNI', 'transferencia' => 'Transferencia', 'mercadopago' => 'Mercado Pago', 'efectivo' => 'Efectivo'];
$pagoCount = []; $pagoTotal = [];
foreach ($metodos as $k => $v) {
    $f = array_filter($pedidos, fn($p) => $p['metodo_pago'] === $k);
    $pagoCount[$k] = count($f);
    $pagoTotal[$k] = array_sum(array_map(fn($p) => floatval($p['total']), $f));
}

// Últimos 5 pedidos confirmados para "conciliación bancaria" (simulado)
$confirmados = array_filter($pedidos, fn($p) => in_array($p['estado'], ['confirmado', 'enviado', 'entregado']));
$conciliados = array_slice(array_values($confirmados), 0, 5);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Admin - ShopRive</title>
  <link rel="stylesheet" href="../css/style.css">
  <?php require_once __DIR__ . '/../config/apariencia.php'; renderThemeStyles(); ?>
  <style>
    .admin-layout { display: flex; min-height: 100vh; }
    .admin-sidebar { width: 260px; background: var(--bg-card); border-right: 1px solid var(--border); padding: 24px; flex-shrink: 0; }
    .admin-sidebar h2 { font-size: 1.2rem; margin-bottom: 24px; }
    .admin-sidebar a { display: block; padding: 12px 16px; color: var(--text-muted); text-decoration: none; border-radius: 12px; margin-bottom: 4px; transition: all 0.2s; }
    .admin-sidebar a:hover, .admin-sidebar a.active { background: var(--bg-card-hover); color: var(--text); }
    .admin-main { flex: 1; padding: 40px; min-width: 0; }
    .admin-main h1 { font-size: 2rem; margin-bottom: 8px; }
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 32px; }
    .stat-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; transition: 0.2s; }
    .stat-card:hover { border-color: var(--primary); }
    .stat-card .num { font-size: 1.8rem; font-weight: 700; color: var(--primary); }
    .stat-card .label { color: var(--text-muted); font-size: 0.85rem; margin-top: 4px; }
    .stat-card .sub { font-size: 0.8rem; color: var(--text-muted); margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border); }
    .analytics-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 32px; }
    .analytics-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; }
    .analytics-card h3 { font-size: 1rem; margin-bottom: 16px; color: var(--text-muted); display: flex; align-items: center; gap: 8px; }
    .analytics-card h3 svg { width: 20px; height: 20px; opacity: 0.6; }
    .bar-row { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
    .bar-label { width: 120px; font-size: 0.85rem; flex-shrink: 0; color: var(--text); }
    .bar-track { flex: 1; height: 24px; background: var(--bg); border-radius: 12px; overflow: hidden; position: relative; }
    .bar-fill { height: 100%; border-radius: 12px; transition: width 0.6s ease; display: flex; align-items: center; padding-left: 10px; font-size: 0.75rem; font-weight: 600; color: white; }
    .bar-value { min-width: 60px; text-align: right; font-size: 0.85rem; font-weight: 600; color: var(--text); flex-shrink: 0; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--border); }
    th { color: var(--text-muted); font-weight: 600; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; }
    .conciliado-badge { background: rgba(0,184,148,0.15); color: var(--success); font-size: 0.75rem; padding: 2px 10px; border-radius: 50px; }
    .section-title { font-size: 1.1rem; margin-bottom: 16px; margin-top: 32px; color: var(--text); display: flex; align-items: center; gap: 10px; }
    .section-title svg { width: 22px; height: 22px; opacity: 0.5; }
    @media (max-width: 768px) { .analytics-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <h2>ShopRive Admin</h2>
      <a href="index.php" class="active">Dashboard</a>
      <a href="productos.php">Productos</a>
      <a href="categorias.php">Categorías</a>
      <a href="pedidos.php">Pedidos</a>
      <a href="usuarios.php">Usuarios</a>
      <a href="chatbot.php">Chatbot</a>
      <a href="configuracion.php">Configuración</a>
      <a href="../auth/logout.php">Cerrar Sesión</a>
      <a href="../index.php" style="margin-top:20px;color:var(--accent);">← Tienda</a>
    </aside>
    <main class="admin-main">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div><h1 style="margin-bottom:0;">Dashboard</h1><p style="color:var(--text-muted);font-size:0.9rem;">Panel de control y analytics</p></div>
        <a href="../index.php" class="btn-primary" style="text-decoration:none;background:transparent;border:1px solid var(--border);font-size:0.9rem;padding:10px 20px;" target="_blank">Ver Tienda</a>
      </div>

      <!-- MÉTRICAS RÁPIDAS -->
      <div class="stats">
        <div class="stat-card"><div class="num"><?= count($pedidos) ?></div><div class="label">Pedidos Totales</div></div>
        <div class="stat-card"><div class="num" style="color:var(--accent);"><?= $pedidosPendientes ?></div><div class="label">Pendientes</div><div class="sub">Esperan confirmación</div></div>
        <div class="stat-card"><div class="num" style="color:var(--success);"><?= $pedidosConfirmados ?></div><div class="label">Confirmados</div></div>
        <div class="stat-card"><div class="num" style="color:var(--primary);">$<?= number_format($totalVentas, 0, ',', '.') ?></div><div class="label">Ventas Totales</div></div>
        <div class="stat-card"><div class="num"><?= $productos ?></div><div class="label">Productos</div></div>
        <div class="stat-card"><div class="num" style="color:<?= $stockBajo > 0 ? 'var(--accent)' : 'var(--success)' ?>"><?= $stockBajo ?></div><div class="label">Stock Bajo</div></div>
        <div class="stat-card"><div class="num"><?= $usuarios ?></div><div class="label">Usuarios</div></div>
        <div class="stat-card"><div class="num"><?= $mensajes ?></div><div class="label">Consultas Chat</div></div>
      </div>

      <!-- ANALYTICS -->
      <div class="analytics-grid">
        <!-- Por tipo de envío -->
        <div class="analytics-card">
          <h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg> Ventas por tipo de envío</h3>
          <?php
            $maxEnvio = max($totalDomicilio ?: 1, $totalRetiro ?: 1);
            $pctDom = round($totalDomicilio / max($totalVentas, 1) * 100);
            $pctRet = round($totalRetiro / max($totalVentas, 1) * 100);
          ?>
          <div class="bar-row">
            <span class="bar-label">Online</span>
            <div class="bar-track"><div class="bar-fill" style="width:<?= $pctDom ?>%;background:linear-gradient(90deg,var(--primary),#8b7cf7);"><?= $pctDom ?>%</div></div>
            <span class="bar-value">$<?= number_format($totalDomicilio, 0, ',', '.') ?></span>
          </div>
          <div class="bar-row">
            <span class="bar-label">Retiro local</span>
            <div class="bar-track"><div class="bar-fill" style="width:<?= $pctRet ?>%;background:linear-gradient(90deg,var(--accent),#fd9ab8);"><?= $pctRet ?>%</div></div>
            <span class="bar-value">$<?= number_format($totalRetiro, 0, ',', '.') ?></span>
          </div>
        </div>

        <!-- Por método de pago -->
        <div class="analytics-card">
          <h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg> Ventas por método de pago</h3>
          <?php $maxPago = max($pagoTotal ?: [1]); ?>
          <?php $colores = ['tarjeta' => '#6c5ce7', 'qr' => '#00b894', 'transferencia' => '#fdcb6e', 'mercadopago' => '#00cec9', 'efectivo' => '#e17055']; ?>
          <?php foreach ($metodos as $k => $v):
            $pct = $pagoTotal[$k] > 0 ? round($pagoTotal[$k] / max($totalVentas, 1) * 100) : 0;
          ?>
          <div class="bar-row">
            <span class="bar-label"><?= $v ?></span>
            <div class="bar-track"><div class="bar-fill" style="width:<?= max($pct, 1) ?>%;background:<?= $colores[$k] ?>;opacity:0.85;"><?= $pct ?>%</div></div>
            <span class="bar-value">$<?= number_format($pagoTotal[$k], 0, ',', '.') ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- CONCILIACIÓN BANCARIA (SIMULADA) -->
      <div class="analytics-card" style="margin-bottom:32px;">
        <h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2"/><path d="M6 6h.01M6 10h.01M6 14h.01M6 18h.01"/><path d="M10 6h8M10 10h8M10 14h8M10 18h8"/></svg> Conciliación Bancaria <span style="font-size:0.75rem;color:var(--text-muted);font-weight:400;">(simulacro — últimos confirmados)</span></h3>
        <?php if (empty($conciliados)): ?>
          <p style="color:var(--text-muted);font-size:0.9rem;">No hay pedidos confirmados para conciliar.</p>
        <?php else: ?>
        <table>
          <tr><th>#</th><th>Cliente</th><th>Monto</th><th>Método</th><th>Estado</th><th>Conciliación</th></tr>
          <?php foreach ($conciliados as $p):
            $metodoLabel = $metodos[$p['metodo_pago']] ?? $p['metodo_pago'];
            $estadoColors = ['pendiente' => '#fdcb6e', 'confirmado' => '#6c5ce7', 'enviado' => '#00b894', 'entregado' => '#00cec9', 'cancelado' => '#e17055'];
            $estados = ['pendiente' => 'Pendiente', 'confirmado' => 'Confirmado', 'enviado' => 'Enviado', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];
            $bancos = ['tarjeta' => 'Visa/Mastercard', 'qr' => 'Cuenta DNI', 'transferencia' => 'Banco Nación', 'mercadopago' => 'Mercado Pago', 'efectivo' => '—'];
          ?>
          <tr>
            <td><?= $p['id'] ?></td>
            <td><?= htmlspecialchars($p['nombre']) ?></td>
            <td>$<?= number_format(floatval($p['total']), 0, ',', '.') ?></td>
            <td style="font-size:0.85rem;"><?= $metodoLabel ?></td>
            <td><span class="badge" style="background:<?= $estadoColors[$p['estado']] ?? '#888' ?>33;color:<?= $estadoColors[$p['estado']] ?? '#888' ?>"><?= $estados[$p['estado']] ?? $p['estado'] ?></span></td>
            <td><span class="conciliado-badge">✓ Conciliado — <?= $bancos[$p['metodo_pago']] ?? 'Manual' ?></span></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
      </div>

      <!-- ÚLTIMOS PEDIDOS -->
      <?php if (!empty($pedidos)): ?>
      <h3 class="section-title">Últimos Pedidos</h3>
      <table>
        <tr><th>#</th><th>Cliente</th><th>Total</th><th>Pago</th><th>Envío</th><th>Estado</th></tr>
        <?php
          $estadoColors = ['pendiente' => '#fdcb6e', 'confirmado' => '#6c5ce7', 'saliendo' => '#00b894', 'salio' => '#00cec9', 'no_salio' => '#e17055', 'se_retiro' => '#a29bfe', 'entregado' => '#00b894', 'cancelado' => '#e17055'];
          $estados = ['pendiente' => 'Pendiente', 'confirmado' => 'Confirmado', 'saliendo' => 'Saliendo', 'salio' => 'Salió', 'no_salio' => 'No salió', 'se_retiro' => 'Se retiró', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];
        ?>
        <?php foreach (array_slice($pedidos, 0, 10) as $p): ?>
        <tr>
          <td><?= $p['id'] ?></td>
          <td><?= htmlspecialchars($p['nombre']) ?></td>
          <td>$<?= number_format(floatval($p['total']), 0, ',', '.') ?></td>
          <td style="font-size:0.85rem;"><?= $metodos[$p['metodo_pago']] ?? $p['metodo_pago'] ?></td>
          <td style="font-size:0.85rem;"><?= $p['tipo_envio'] === 'retiro' ? 'Retiro' : 'Online' ?></td>
          <td><span class="badge" style="background:<?= $estadoColors[$p['estado']] ?? '#888' ?>33;color:<?= $estadoColors[$p['estado']] ?? '#888' ?>"><?= $estados[$p['estado']] ?? $p['estado'] ?></span></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <a href="pedidos.php" style="display:inline-block;margin-top:12px;color:var(--primary);text-decoration:none;font-weight:600;">Ver todos los pedidos →</a>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
