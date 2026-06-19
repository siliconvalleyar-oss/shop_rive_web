<?php
/**
 * Checkout API - Order creation and payment processing
 *
 * Routes (via api/index.php):
 *   POST /api/checkout
 *   POST /api/checkout/{id}/pay
 *
 * Legacy: checkout.php?action=create|pay
 */

require_once __DIR__ . '/../lib/bootstrap.php';

// Handle legacy ?action= format
$action = $_GET['action'] ?? '';
if ($action && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
  switch ($action) {
    case 'create': handleCreateOrder(); break;
    case 'pay': handlePayOrder(0); break;
    default: Response::error('Acción no válida');
  }
}

/**
 * POST /api/checkout
 */
function handleCreateOrder() {
  $data = getJsonBody();

  $v = new Validator($data, [
    'nombre' => 'Nombre completo',
    'email' => 'Email',
    'telefono' => 'Teléfono',
    'metodo_pago' => 'Método de pago',
    'items' => 'Productos'
  ]);
  $v->required('nombre', 'email', 'telefono', 'metodo_pago', 'items')
    ->email('email')
    ->inArray('metodo_pago', ['tarjeta', 'qr', 'transferencia', 'mercadopago', 'efectivo']);

  if (!$v->passes()) {
    Response::error($v->firstError(), 422);
  }

  $nombre   = trim($data['nombre']);
  $email    = trim($data['email']);
  $telefono = trim($data['telefono']);
  $direccion = trim($data['direccion'] ?? '');
  $localidad = trim($data['localidad'] ?? '');
  $entre_calles = trim($data['entre_calles'] ?? '');
  $coordenadas = trim($data['coordenadas'] ?? '');
  $comentarios_ubicacion = trim($data['comentarios_ubicacion'] ?? '');
  $metodo   = trim($data['metodo_pago']);
  $tipo_envio = trim($data['tipo_envio'] ?? 'domicilio');
  $items    = $data['items'] ?? [];
  $notas    = trim($data['notas'] ?? '');

  if ($tipo_envio === 'domicilio' && (!$direccion || !$localidad)) {
    Response::error('Completá la calle y localidad de envío', 422);
  }

  if (empty($items)) {
    Response::error('Agregá productos al carrito', 422);
  }

  global $pdo;

  // Check for solo_retiro products
  $ids = array_map(fn($i) => intval($i['id']), $items);
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $stmtCheck = $pdo->prepare("SELECT COUNT(*) as c FROM productos WHERE id IN ($placeholders) AND solo_retiro = 1");
  $stmtCheck->execute($ids);
  $row = $stmtCheck->fetch();
  if ((int)($row['c'] ?? 0) > 0) {
    $tipo_envio = 'retiro';
  }

  $total = array_reduce($items, fn($sum, $item) => $sum + floatval($item['price']) * intval($item['qty']), 0);
  $usuario_id = $_SESSION['user_id'] ?? null;

  try {
    $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, nombre, email, telefono, direccion, localidad, entre_calles, coordenadas, comentarios_ubicacion, metodo_pago, tipo_envio, total, estado, notas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', ?)");
    $stmt->execute([$usuario_id, $nombre, $email, $telefono, $direccion, $localidad, $entre_calles, $coordenadas, $comentarios_ubicacion, $metodo, $tipo_envio, $total, $notas]);
    $pedido_id = $pdo->lastInsertId();

    $stmtItem = $pdo->prepare("INSERT INTO detalle_pedido (pedido_id, producto_id, nombre, precio, cantidad) VALUES (?, ?, ?, ?, ?)");
    foreach ($items as $item) {
      $stmtItem->execute([$pedido_id, intval($item['id']), $item['name'], floatval($item['price']), intval($item['qty'])]);
    }

    // Send reception email
    require_once __DIR__ . '/../config/email.php';
    $pedidoData = [
      'id' => $pedido_id, 'nombre' => $nombre, 'email' => $email,
      'tipo_envio' => $tipo_envio, 'metodo_pago' => $metodo, 'total' => $total
    ];
    $itemsData = array_map(fn($i) => ['nombre' => $i['name'], 'precio' => $i['price'], 'cantidad' => $i['qty']], $items);
    enviarEmailTipo('recepcion', $email, $pedidoData, $itemsData);

    Logger::info("Pedido #$pedido_id creado", ['metodo' => $metodo, 'total' => $total]);

    Response::success([
      'pedido_id' => $pedido_id
    ], 'Pedido creado');
  } catch (Exception $e) {
    Logger::error("Error al crear pedido: " . $e->getMessage());
    Response::error('Error al crear el pedido', 500);
  }
}

/**
 * POST /api/checkout/{id}/pay
 */
function handlePayOrder(int $pedido_id_route = 0) {
  $data = getJsonBody();
  $pedido_id = $pedido_id_route ?: intval($data['pedido_id'] ?? 0);

  $card_number = preg_replace('/\s/', '', $data['card_number'] ?? '');
  $card_name   = trim($data['card_name'] ?? '');
  $card_expiry = trim($data['card_expiry'] ?? '');
  $card_cvv    = trim($data['card_cvv'] ?? '');

  $v = new Validator($data, [
    'card_name' => 'Titular de la tarjeta',
    'card_number' => 'Número de tarjeta',
    'card_expiry' => 'Fecha de vencimiento',
    'card_cvv' => 'Código de seguridad'
  ]);
  $v->required('card_name', 'card_number', 'card_expiry', 'card_cvv');

  if (!$v->passes()) {
    Response::error($v->firstError(), 422);
  }

  // Card validation
  if (strlen($card_number) < 13 || strlen($card_number) > 19) {
    Response::error('Número de tarjeta inválido', 422);
  }
  if (!preg_match('/^\d{3,4}$/', $card_cvv)) {
    Response::error('Código de seguridad inválido', 422);
  }
  if (!preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) {
    Response::error('Fecha de vencimiento inválida (MM/AA)', 422);
  }

  if (!$pedido_id) {
    Response::error('Pedido requerido', 422);
  }

  global $pdo;

  try {
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND estado = 'pendiente'");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
      Response::error('Pedido no encontrado o ya procesado', 404);
    }

    // Get order items
    $stmtItems = $pdo->prepare("SELECT * FROM detalle_pedido WHERE pedido_id = ?");
    $stmtItems->execute([$pedido_id]);
    $items = $stmtItems->fetchAll();

    // Update stock
    $stmtStock = $pdo->prepare("UPDATE productos SET stock = MAX(0, stock - ?) WHERE id = ? AND stock >= ?");
    foreach ($items as $item) {
      $stmtStock->execute([intval($item['cantidad']), intval($item['producto_id']), intval($item['cantidad'])]);
    }

    // Generate invoice data
    $dataFile = __DIR__ . '/../data/facturacion.json';
    $factData = file_exists($dataFile) ? (json_decode(file_get_contents($dataFile), true) ?: []) : [];
    if (!isset($factData['ultimo_numero'])) $factData['ultimo_numero'] = 0;
    $factData['ultimo_numero']++;
    $numeroFactura = '0001-' . str_pad($factData['ultimo_numero'], 8, '0', STR_PAD_LEFT);
    $cae = str_pad(random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $caeVto = date('d/m/Y', strtotime('+30 days'));
    $fechaFactura = date('d/m/Y');
    file_put_contents($dataFile, json_encode($factData));

    // Mark as confirmed
    $pdo->prepare("UPDATE pedidos SET estado = 'confirmado', numero_factura = ?, cae = ?, cae_vencimiento = ?, fecha_factura = ? WHERE id = ?")
      ->execute([$numeroFactura, $cae, $caeVto, $fechaFactura, $pedido_id]);
    $pedido['numero_factura'] = $numeroFactura;
    $pedido['cae'] = $cae;

    // Clear cart for logged-in users
    $usuario_id = $_SESSION['user_id'] ?? null;
    if ($usuario_id) {
      $pdo->prepare("DELETE FROM carrito WHERE usuario_id = ?")->execute([$usuario_id]);
    }

    // Send emails
    require_once __DIR__ . '/../config/email.php';
    $proveedor_email = 'proveedores@shoprive.com';
    $shop_name = 'ShopRive';
    $nombre = $pedido['nombre'];
    $email = $pedido['email'];

    $itemsData = [];
    foreach ($items as $item) {
      $itemsData[] = ['nombre' => $item['nombre'], 'precio' => $item['precio'], 'cantidad' => $item['cantidad']];
    }

    // Emails to customer
    enviarEmailTipo('pago_confirmado', $email, $pedido, $itemsData, ['cardLast4' => substr($card_number, -4)]);
    enviarEmailTipo('factura', $email, $pedido, $itemsData);

    // Email to supplier
    $totalFormatted = '$' . number_format(floatval($pedido['total']), 0, ',', '.');
    $labels = ['transferencia' => 'Transferencia Bancaria', 'tarjeta' => 'Tarjeta de Crédito/Débito', 'qr' => 'QR - Cuenta DNI', 'efectivo' => 'Efectivo', 'mercadopago' => 'Mercado Pago'];
    $metodoLabel = $labels[$pedido['metodo_pago']] ?? $pedido['metodo_pago'];
    $envioLabels = ['domicilio' => 'Envío a domicilio', 'retiro' => 'Retiro en local'];
    $envioLabel = $envioLabels[$pedido['tipo_envio']] ?? $pedido['tipo_envio'];
    $itemsHtml = '';
    foreach ($items as $item) {
      $subtotal = floatval($item['precio']) * intval($item['cantidad']);
      $itemsHtml .= "<tr><td style='padding:8px;border-bottom:1px solid #ddd;'>{$item['nombre']}</td><td style='padding:8px;border-bottom:1px solid #ddd;text-align:center;'>{$item['cantidad']}</td><td style='padding:8px;border-bottom:1px solid #ddd;text-align:right;'>$" . number_format(floatval($item['precio']), 0, ',', '.') . "</td><td style='padding:8px;border-bottom:1px solid #ddd;text-align:right;'>$" . number_format($subtotal, 0, ',', '.') . "</td></tr>";
    }
    $subjectProv = "=?UTF-8?B?" . base64_encode("Nuevo Pago - Pedido #{$pedido['id']} - $shop_name") . "?=";
    $bodyProv = "
    <html><body style='font-family:Arial,sans-serif;background:#f5f5f5;padding:20px;'>
    <div style='max-width:600px;margin:auto;background:#fff;border-radius:12px;padding:32px;'>
    <h2 style='color:#6c5ce7;'>Nuevo Pago Confirmado - Pedido #{$pedido['id']}</h2>
    <p><strong>Cliente:</strong> {$pedido['nombre']}</p>
    <p><strong>Email:</strong> {$pedido['email']}</p>
    <p><strong>Dirección:</strong> " . ($pedido['tipo_envio'] === 'retiro' ? 'Retiro en local - Av. Corrientes 1234' : $pedido['direccion']) . "</p>
    <p><strong>Envío:</strong> $envioLabel</p>
    <p><strong>Pago:</strong> $metodoLabel</p>
    <p><strong>Tarjeta:</strong> **** **** **** " . substr($card_number, -4) . "</p>
    <p><strong>Factura:</strong> {$pedido['numero_factura']} — CAE: {$pedido['cae']}</p>
    <table style='width:100%;border-collapse:collapse;margin:20px 0;'>
    <tr><th style='text-align:left;padding:8px;border-bottom:2px solid #6c5ce7;'>Producto</th><th style='text-align:center;padding:8px;border-bottom:2px solid #6c5ce7;'>Cant.</th><th style='text-align:right;padding:8px;border-bottom:2px solid #6c5ce7;'>Precio</th><th style='text-align:right;padding:8px;border-bottom:2px solid #6c5ce7;'>Subtotal</th></tr>
    $itemsHtml
    <tr><td colspan='3' style='text-align:right;padding:12px;font-weight:700;font-size:1.1rem;'>Total:</td><td style='text-align:right;padding:12px;font-weight:700;font-size:1.1rem;color:#fd79a8;'>$totalFormatted</td></tr>
    </table>
    <p style='color:#888;font-size:0.9rem;'>Preparar pedido para envío/retiro.</p>
    </div></body></html>";
    $headersProv = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: $shop_name <noreply@shoprive.com>\r\n";
    @mail($proveedor_email, $subjectProv, $bodyProv, $headersProv);

    Logger::info("Pago procesado Pedido #$pedido_id", ['factura' => $numeroFactura]);

    Response::success([
      'pedido_id' => $pedido_id
    ], 'Pago procesado con éxito');
  } catch (Exception $e) {
    Logger::error("Error al procesar pago #$pedido_id: " . $e->getMessage());
    Response::error('Error al procesar el pago', 500);
  }
}
