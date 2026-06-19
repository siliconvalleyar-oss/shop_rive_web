<?php
/**
 * Presupuesto API - Quote/budget generation
 *
 * Routes (via api/index.php):
 *   POST /api/presupuesto
 *
 * Legacy: presupuesto.php (no action needed)
 */

require_once __DIR__ . '/../lib/bootstrap.php';

// Handle legacy direct call
$action = $_GET['action'] ?? '';

/**
 * POST /api/presupuesto
 */
function handlePresupuesto() {
  $data = getJsonBody();

  $v = new Validator($data, [
    'nombre' => 'Nombre',
    'email' => 'Email',
    'items' => 'Productos'
  ]);
  $v->required('nombre', 'email', 'items')->email('email');

  if (!$v->passes()) {
    Response::error($v->firstError(), 422);
  }

  $nombre = trim($data['nombre']);
  $email = trim($data['email']);
  $telefono = trim($data['telefono'] ?? '');
  $items = $data['items'];

  if (empty($items)) {
    Response::error('Agregá al menos un producto al presupuesto', 422);
  }

  // Generate sequential ID
  $dataFile = __DIR__ . '/../data/presupuestos.json';
  $presupuestos = file_exists($dataFile) ? (json_decode(file_get_contents($dataFile), true) ?: []) : [];
  $presupuestoId = count($presupuestos) + 1;

  // Assign estimated prices by category
  $categoryPrices = [
    'electronica' => 45000,
    'moda' => 38000,
    'hogar' => 18000,
    'deportes' => 35000
  ];

  $itemsData = [];
  foreach ($items as $item) {
    $cat = $item['categoria'] ?? '';
    $price = $categoryPrices[$cat] ?? 25000;
    $itemsData[] = [
      'nombre' => $item['nombre'] ?? 'Producto',
      'precio' => $price,
      'cantidad' => intval($item['cantidad'] ?? 1),
      'categoria' => $cat
    ];
  }

  $total = array_reduce($itemsData, fn($sum, $i) => $sum + $i['precio'] * $i['cantidad'], 0);

  // Send email
  require_once __DIR__ . '/../config/email.php';
  $cliente = ['nombre' => $nombre, 'email' => $email, 'telefono' => $telefono];
  $pedido = ['id' => $presupuestoId, 'total' => $total];

  enviarEmailTipo('presupuesto', $email, $pedido, $itemsData, [
    'presupuesto_id' => $presupuestoId,
    'cliente' => $cliente
  ]);

  // Also send copy to admin
  enviarEmailTipo('presupuesto', 'proveedores@shoprive.com', $pedido, $itemsData, [
    'presupuesto_id' => $presupuestoId,
    'cliente' => $cliente
  ]);

  // Save to file
  $presupuestos[] = [
    'id' => $presupuestoId,
    'nombre' => $nombre,
    'email' => $email,
    'total' => $total,
    'items' => $itemsData,
    'fecha' => date('Y-m-d H:i:s')
  ];
  file_put_contents($dataFile, json_encode($presupuestos, JSON_PRETTY_PRINT));

  Logger::info("Presupuesto #$presupuestoId generado para $email", ['total' => $total]);

  Response::success([
    'presupuesto_id' => $presupuestoId,
    'total' => $total
  ], 'Presupuesto generado. Te enviamos los detalles por email.');
}
