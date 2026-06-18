<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

// Cambiar estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['estado'])) {
    $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?")->execute([$_POST['estado'], $_POST['id']]);
    header('Location: pedidos.php');
    exit;
}

$pedidos = $pdo->query("SELECT * FROM pedidos ORDER BY id DESC")->fetchAll();

$estados = ['pendiente' => 'Pendiente', 'confirmado' => 'Confirmado', 'saliendo' => 'Saliendo', 'salio' => 'Salió', 'no_salio' => 'No salió', 'se_retiro' => 'Se retiró', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];
$estadoColors = ['pendiente' => '#fdcb6e', 'confirmado' => '#6c5ce7', 'saliendo' => '#00b894', 'salio' => '#00cec9', 'no_salio' => '#e17055', 'se_retiro' => '#a29bfe', 'entregado' => '#00b894', 'cancelado' => '#e17055'];
$envioLabels = ['domicilio' => 'A domicilio', 'retiro' => 'Retiro en local'];
$metodoLabels = ['tarjeta' => 'Tarjeta', 'transferencia' => 'Transf.', 'qr' => 'Cuenta DNI', 'efectivo' => 'Efectivo', 'mercadopago' => 'Mercado Pago'];

// Obtener items de cada pedido
$itemsPorPedido = [];
foreach ($pedidos as $p) {
    $items = $pdo->prepare("SELECT * FROM detalle_pedido WHERE pedido_id = ?");
    $items->execute([$p['id']]);
    $itemsPorPedido[$p['id']] = $items->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pedidos - Admin ShopRive</title>
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
    .estado-badge { display: inline-block; padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 600; }
    .estado-select { padding: 6px 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg); color: var(--text); font-size: 0.85rem; }
    .order-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; margin-bottom: 16px; }
    .order-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
    .order-header h3 { font-size: 1.1rem; }
    .order-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 8px; margin-bottom: 16px; font-size: 0.9rem; }
    .order-meta span { color: var(--text-muted); }
    .order-meta strong { color: var(--text); }
    .items-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    .items-table th { font-size: 0.8rem; padding: 8px; }
    .items-table td { padding: 8px; font-size: 0.9rem; }
    .total-row { font-weight: 700; font-size: 1.1rem; }
    .total-row td { border-top: 2px solid var(--primary); padding-top: 12px; }
    .no-orders { text-align: center; padding: 60px 20px; color: var(--text-muted); }
    .no-orders svg { width: 64px; height: 64px; opacity: 0.3; margin-bottom: 16px; }
    .filter-bar { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
    .filter-bar select, .filter-bar input { padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); background: var(--bg); color: var(--text); font-size: 0.9rem; }
  </style>
</head>
<body>
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <h2>ShopRive Admin</h2>
      <a href="index.php">Dashboard</a>
      <a href="productos.php">Productos</a>
      <a href="categorias.php">Categorías</a>
      <a href="pedidos.php" class="active">Pedidos</a>
      <a href="usuarios.php">Usuarios</a>
      <a href="chatbot.php">Chatbot</a>
      <a href="configuracion.php">Configuración</a>
      <a href="../auth/logout.php">Cerrar Sesión</a>
      <a href="../index.php" style="margin-top:20px;color:var(--accent);">← Tienda</a>
    </aside>
    <main class="admin-main">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <h1 style="margin-bottom:0;">Pedidos</h1>
        <a href="../index.php#productos" class="btn-primary" style="text-decoration:none;background:transparent;border:1px solid var(--border);font-size:0.9rem;padding:10px 20px;" target="_blank">Ver Tienda</a>
      </div>

      <?php if (empty($pedidos)): ?>
        <div class="no-orders">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
          <p>No hay pedidos todavía.</p>
        </div>
      <?php else: ?>
        <?php foreach ($pedidos as $p):
          $items = $itemsPorPedido[$p['id']] ?? [];
          $total = array_sum(array_map(fn($i) => floatval($i['precio']) * intval($i['cantidad']), $items));
        ?>
        <div class="order-card">
          <div class="order-header">
            <h3>Pedido #<?= $p['id'] ?> <span class="estado-badge" style="background:<?= $estadoColors[$p['estado']] ?? '#888' ?>33;color:<?= $estadoColors[$p['estado']] ?? '#888' ?>"><?= $estados[$p['estado']] ?? $p['estado'] ?></span>
              <?php if (!empty($p['numero_factura'])): ?>
                <span style="font-size:0.8rem;font-weight:400;color:var(--success);margin-left:8px;">Factura <?= $p['numero_factura'] ?></span>
              <?php endif; ?>
            </h3>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
              <a href="factura.php?id=<?= $p['id'] ?>" class="btn-sm" style="padding:6px 14px;border-radius:8px;border:1px solid var(--success);background:transparent;color:var(--success);cursor:pointer;text-decoration:none;font-size:0.85rem;" target="_blank">Factura</a>
              <form method="POST" style="display:flex;gap:8px;align-items:center;">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <select name="estado" class="estado-select">
                  <?php foreach ($estados as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $k === $p['estado'] ? 'selected' : '' ?>><?= $v ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn-sm" style="padding:6px 14px;border-radius:8px;border:1px solid var(--primary);background:transparent;color:var(--primary);cursor:pointer;">Actualizar</button>
              </form>
            </div>
          </div>

          <div class="order-meta">
            <span><strong>Cliente:</strong> <?= htmlspecialchars($p['nombre']) ?></span>
            <span><strong>Email:</strong> <?= htmlspecialchars($p['email']) ?></span>
            <span><strong>Teléfono:</strong> <?= htmlspecialchars($p['telefono']) ?></span>
            <span><strong>Fecha:</strong> <?= $p['created_at'] ?? '-' ?></span>
            <span><strong>Pago:</strong> <?= $metodoLabels[$p['metodo_pago']] ?? $p['metodo_pago'] ?></span>
            <?php if (!empty($p['numero_factura'])): ?>
            <span><strong>Factura:</strong> <?= $p['numero_factura'] ?></span>
            <span><strong>CAE:</strong> <?= $p['cae'] ?></span>
            <?php endif; ?>
            <span><strong>Envío:</strong> <?= $envioLabels[$p['tipo_envio'] ?? 'domicilio'] ?? 'A domicilio' ?></span>
            <?php if (($p['tipo_envio'] ?? 'domicilio') !== 'retiro'): ?>
            <span style="grid-column:1/-1;"><strong>Dirección:</strong> <?= htmlspecialchars($p['direccion']) ?><?= !empty($p['localidad']) ? ', ' . htmlspecialchars($p['localidad']) : '' ?></span>
            <?php if (!empty($p['entre_calles'])): ?><span><strong>Entre calles:</strong> <?= htmlspecialchars($p['entre_calles']) ?></span><?php endif; ?>
            <?php if (!empty($p['coordenadas'])): ?><span><strong>Coordenadas:</strong> <?= htmlspecialchars($p['coordenadas']) ?></span><?php endif; ?>
            <?php if (!empty($p['comentarios_ubicacion'])): ?><span style="grid-column:1/-1;"><strong>Comentarios:</strong> <?= htmlspecialchars($p['comentarios_ubicacion']) ?></span><?php endif; ?>
            <?php else: ?>
            <span style="grid-column:1/-1;"><strong>Retiro:</strong> Av. Corrientes 1234, Buenos Aires</span>
            <?php endif; ?>
          </div>

          <?php if (!empty($items)): ?>
          <table class="items-table">
            <tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr>
            <?php foreach ($items as $i): ?>
            <tr>
              <td><?= htmlspecialchars($i['nombre']) ?></td>
              <td><?= (int)$i['cantidad'] ?></td>
              <td>$<?= number_format(floatval($i['precio']), 0, ',', '.') ?></td>
              <td>$<?= number_format(floatval($i['precio']) * intval($i['cantidad']), 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row"><td colspan="3" style="text-align:right;">Total</td><td>$<?= number_format($total, 0, ',', '.') ?></td></tr>
          </table>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
