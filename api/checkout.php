<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Método no permitido']);
  exit;
}

if (!$pdo) {
  echo json_encode(['success' => false, 'message' => 'Error de conexión. Intentá de nuevo.']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
  echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
  exit;
}

// --- ACTION: create (guardar pedido pendiente, no tocar carrito ni stock) ---
if ($action === 'create') {
  $nombre   = trim($data['nombre'] ?? '');
  $email    = trim($data['email'] ?? '');
  $telefono = trim($data['telefono'] ?? '');
  $direccion = trim($data['direccion'] ?? '');
  $localidad = trim($data['localidad'] ?? '');
  $entre_calles = trim($data['entre_calles'] ?? '');
  $coordenadas = trim($data['coordenadas'] ?? '');
  $comentarios_ubicacion = trim($data['comentarios_ubicacion'] ?? '');
  $metodo   = trim($data['metodo_pago'] ?? '');
  $tipo_envio = trim($data['tipo_envio'] ?? 'domicilio');
  $items    = $data['items'] ?? [];
  $notas    = trim($data['notas'] ?? '');

  if (!$nombre || !$email || !$telefono || !$metodo || empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Completá todos los campos requeridos']);
    exit;
  }

  if ($tipo_envio === 'domicilio' && (!$direccion || !$localidad)) {
    echo json_encode(['success' => false, 'message' => 'Completá la calle y localidad de envío']);
    exit;
  }

  $total = 0;
  foreach ($items as $item) {
    $total += floatval($item['price']) * intval($item['qty']);
  }

  $usuario_id = $_SESSION['user_id'] ?? null;

  try {
    $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, nombre, email, telefono, direccion, localidad, entre_calles, coordenadas, comentarios_ubicacion, metodo_pago, tipo_envio, total, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')");
    $stmt->execute([$usuario_id, $nombre, $email, $telefono, $direccion, $localidad, $entre_calles, $coordenadas, $comentarios_ubicacion, $metodo, $tipo_envio, $total]);
    $pedido_id = $pdo->lastInsertId();

    $stmtItem = $pdo->prepare("INSERT INTO detalle_pedido (pedido_id, producto_id, nombre, precio, cantidad) VALUES (?, ?, ?, ?, ?)");
    foreach ($items as $item) {
      $stmtItem->execute([$pedido_id, intval($item['id']), $item['name'], floatval($item['price']), intval($item['qty'])]);
    }

    echo json_encode(['success' => true, 'pedido_id' => $pedido_id, 'message' => 'Pedido creado']);
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al crear el pedido']);
  }
  exit;
}

// --- ACTION: pay (procesar pago, actualizar stock, vaciar carrito, enviar emails) ---
if ($action === 'pay') {
  $pedido_id = intval($data['pedido_id'] ?? 0);
  $card_number = preg_replace('/\s/', '', $data['card_number'] ?? '');
  $card_name   = trim($data['card_name'] ?? '');
  $card_expiry = trim($data['card_expiry'] ?? '');
  $card_cvv    = trim($data['card_cvv'] ?? '');

  if (!$pedido_id || !$card_number || !$card_name || !$card_expiry || !$card_cvv) {
    echo json_encode(['success' => false, 'message' => 'Completá todos los datos de la tarjeta']);
    exit;
  }

  // Validación básica de tarjeta
  if (strlen($card_number) < 13 || strlen($card_number) > 19) {
    echo json_encode(['success' => false, 'message' => 'Número de tarjeta inválido']);
    exit;
  }
  if (!preg_match('/^\d{3,4}$/', $card_cvv)) {
    echo json_encode(['success' => false, 'message' => 'Código de seguridad inválido']);
    exit;
  }
  if (!preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) {
    echo json_encode(['success' => false, 'message' => 'Fecha de vencimiento inválida (MM/AA)']);
    exit;
  }

  try {
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND estado = 'pendiente'");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
      echo json_encode(['success' => false, 'message' => 'Pedido no encontrado o ya procesado']);
      exit;
    }

    // Obtener items del pedido
    $stmtItems = $pdo->prepare("SELECT * FROM detalle_pedido WHERE pedido_id = ?");
    $stmtItems->execute([$pedido_id]);
    $items = $stmtItems->fetchAll();

    // Actualizar stock
    $stmtStock = $pdo->prepare("UPDATE productos SET stock = MAX(0, stock - ?) WHERE id = ? AND stock >= ?");
    foreach ($items as $item) {
      $stmtStock->execute([intval($item['cantidad']), intval($item['producto_id']), intval($item['cantidad'])]);
    }

    // Generar datos de facturación
    $dataFile = __DIR__ . '/../data/facturacion.json';
    $factData = file_exists($dataFile) ? (json_decode(file_get_contents($dataFile), true) ?: []) : [];
    if (!isset($factData['ultimo_numero'])) $factData['ultimo_numero'] = 0;
    $factData['ultimo_numero']++;
    $numeroFactura = '0001-' . str_pad($factData['ultimo_numero'], 8, '0', STR_PAD_LEFT);
    $cae = str_pad(random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $caeVto = date('d/m/Y', strtotime('+30 days'));
    $fechaFactura = date('d/m/Y');
    file_put_contents($dataFile, json_encode($factData));

    // Marcar pedido como confirmado y guardar factura
    $pdo->prepare("UPDATE pedidos SET estado = 'confirmado', numero_factura = ?, cae = ?, cae_vencimiento = ?, fecha_factura = ? WHERE id = ?")->execute([$numeroFactura, $cae, $caeVto, $fechaFactura, $pedido_id]);
    $pedido['numero_factura'] = $numeroFactura;
    $pedido['cae'] = $cae;

    // Vaciar carrito del usuario si está logueado
    $usuario_id = $_SESSION['user_id'] ?? null;
    if ($usuario_id) {
      $pdo->prepare("DELETE FROM carrito WHERE usuario_id = ?")->execute([$usuario_id]);
    }

    // --- Enviar emails ---
    $proveedor_email = 'proveedores@shoprive.com';
    $shop_name = 'ShopRive';
    $nombre = $pedido['nombre'];
    $email = $pedido['email'];

    $itemsHtml = '';
    foreach ($items as $item) {
      $subtotal = floatval($item['precio']) * intval($item['cantidad']);
      $itemsHtml .= "<tr><td style='padding:8px;border-bottom:1px solid #ddd;'>{$item['nombre']}</td><td style='padding:8px;border-bottom:1px solid #ddd;text-align:center;'>{$item['cantidad']}</td><td style='padding:8px;border-bottom:1px solid #ddd;text-align:right;'>$" . number_format(floatval($item['precio']), 0, ',', '.') . "</td><td style='padding:8px;border-bottom:1px solid #ddd;text-align:right;'>$" . number_format($subtotal, 0, ',', '.') . "</td></tr>";
    }

    $totalFormatted = '$' . number_format(floatval($pedido['total']), 0, ',', '.');
    $labels = ['transferencia' => 'Transferencia Bancaria', 'tarjeta' => 'Tarjeta de Crédito/Débito', 'qr' => 'QR - Cuenta DNI', 'efectivo' => 'Efectivo', 'mercadopago' => 'Mercado Pago'];
    $metodoLabel = $labels[$pedido['metodo_pago']] ?? $pedido['metodo_pago'];

    $envioLabels = ['domicilio' => 'Envío a domicilio', 'retiro' => 'Retiro en local'];
    $envioLabel = $envioLabels[$pedido['tipo_envio']] ?? $pedido['tipo_envio'];

    if ($pedido['tipo_envio'] === 'retiro') {
      $direccionHtml = '<p><strong>Dirección de retiro:</strong> Av. Corrientes 1234, Buenos Aires (nuestro local)</p>';
    } else {
      $dirDetalle = $pedido['direccion'];
      if (!empty($pedido['localidad'])) $dirDetalle .= ', ' . $pedido['localidad'];
      $direccionHtml = '<p><strong>Dirección de envío:</strong> ' . $dirDetalle . '</p>';
      if (!empty($pedido['entre_calles'])) $direccionHtml .= '<p><strong>Entre calles:</strong> ' . $pedido['entre_calles'] . '</p>';
      if (!empty($pedido['coordenadas'])) $direccionHtml .= '<p><strong>Coordenadas:</strong> ' . $pedido['coordenadas'] . '</p>';
      if (!empty($pedido['comentarios_ubicacion'])) $direccionHtml .= '<p><strong>Comentarios:</strong> ' . $pedido['comentarios_ubicacion'] . '</p>';
    }

    $facturaHtml = "<p><strong>Factura electrónica:</strong> Nº {$pedido['numero_factura']} - CAE: {$pedido['cae']}</p>";

    // Email al cliente
    $subjectCliente = "=?UTF-8?B?" . base64_encode("Pago Confirmado - Pedido #$pedido_id - $shop_name") . "?=";
    $bodyCliente = "
    <html><body style='font-family:Arial,sans-serif;background:#f5f5f5;padding:20px;'>
    <div style='max-width:600px;margin:auto;background:#fff;border-radius:12px;padding:32px;'>
    <h2 style='color:#6c5ce7;'>¡Pago recibido, $nombre!</h2>
    <p>Tu pedido <strong>#$pedido_id</strong> fue confirmado y ya está en proceso.</p>
    <p>Te vamos a notificar cuando sea enviado.</p>
    <table style='width:100%;border-collapse:collapse;margin:20px 0;'>
    <tr><th style='text-align:left;padding:8px;border-bottom:2px solid #6c5ce7;'>Producto</th><th style='text-align:center;padding:8px;border-bottom:2px solid #6c5ce7;'>Cant.</th><th style='text-align:right;padding:8px;border-bottom:2px solid #6c5ce7;'>Precio</th><th style='text-align:right;padding:8px;border-bottom:2px solid #6c5ce7;'>Subtotal</th></tr>
    $itemsHtml
    <tr><td colspan='3' style='text-align:right;padding:12px;font-weight:700;font-size:1.1rem;'>Total:</td><td style='text-align:right;padding:12px;font-weight:700;font-size:1.1rem;color:#fd79a8;'>$totalFormatted</td></tr>
    </table>
    <p><strong>Método de pago:</strong> $metodoLabel</p>
    <p><strong>Forma de envío:</strong> $envioLabel</p>
    $direccionHtml
    $facturaHtml
    <p style='font-size:0.85rem;'>Descargá tu factura desde el panel de administración o solicitándola a soporte@shoprive.com</p>
    <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>
    <p style='color:#888;font-size:0.8rem;'>$shopName - Av. Corrientes 1234, Buenos Aires, Argentina</p>
    </div></body></html>";

    $headersCliente = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: $shopName <noreply@shoprive.com>\r\n";
    @mail($email, $subjectCliente, $bodyCliente, $headersCliente);

    // Email al proveedor
    $subjectProv = "=?UTF-8?B?" . base64_encode("Pago Recibido - Pedido #$pedido_id - $shopName") . "?=";
    $bodyProv = "
    <html><body style='font-family:Arial,sans-serif;background:#f5f5f5;padding:20px;'>
    <div style='max-width:600px;margin:auto;background:#fff;border-radius:12px;padding:32px;'>
    <h2 style='color:#6c5ce7;'>Pago Confirmado - Pedido #$pedido_id</h2>
    <p><strong>Cliente:</strong> {$pedido['nombre']}</p>
    <p><strong>Email:</strong> {$pedido['email']}</p>
    <p><strong>Dirección:</strong> " . ($pedido['tipo_envio'] === 'retiro' ? 'Retiro en local - Av. Corrientes 1234' : $pedido['direccion']) . "</p>
    <p><strong>Forma de envío:</strong> $envioLabel</p>
    <p><strong>Método de pago:</strong> $metodoLabel</p>
    <p><strong>Tarjeta:</strong> **** **** **** " . substr($card_number, -4) . "</p>
    $facturaHtml
    <table style='width:100%;border-collapse:collapse;margin:20px 0;'>
    <tr><th style='text-align:left;padding:8px;border-bottom:2px solid #6c5ce7;'>Producto</th><th style='text-align:center;padding:8px;border-bottom:2px solid #6c5ce7;'>Cant.</th><th style='text-align:right;padding:8px;border-bottom:2px solid #6c5ce7;'>Precio</th><th style='text-align:right;padding:8px;border-bottom:2px solid #6c5ce7;'>Subtotal</th></tr>
    $itemsHtml
    <tr><td colspan='3' style='text-align:right;padding:12px;font-weight:700;font-size:1.1rem;'>Total:</td><td style='text-align:right;padding:12px;font-weight:700;font-size:1.1rem;color:#fd79a8;'>$totalFormatted</td></tr>
    </table>
    <p style='color:#888;font-size:0.9rem;'>Preparar pedido para envío.</p>
    </div></body></html>";

    $headersProv = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: $shopName <noreply@shoprive.com>\r\n";
    @mail($proveedor_email, $subjectProv, $bodyProv, $headersProv);

    echo json_encode(['success' => true, 'pedido_id' => $pedido_id, 'message' => 'Pago procesado con éxito']);
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al procesar el pago']);
  }
  exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
