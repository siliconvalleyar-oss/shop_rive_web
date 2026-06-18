<?php
/**
 * API: Presupuesto / Cotización
 * Genera un presupuesto por email para productos personalizados.
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$nombre = trim($data['nombre'] ?? '');
$email = trim($data['email'] ?? '');
$telefono = trim($data['telefono'] ?? '');
$mensaje = trim($data['mensaje'] ?? '');
$items = $data['items'] ?? []; // [{nombre, cantidad, categoria}]

if (!$nombre || !$email || empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Completá todos los campos requeridos']);
    exit;
}

// Generar número de presupuesto
$dataFile = __DIR__ . '/../data/presupuestos.json';
$presupuestos = file_exists($dataFile) ? (json_decode(file_get_contents($dataFile), true) ?: []) : [];
$nextId = count($presupuestos) + 1;
$presupuestoId = str_pad($nextId, 4, '0', STR_PAD_LEFT);

// Guardar presupuesto
$presupuesto = [
    'id' => $presupuestoId,
    'nombre' => $nombre,
    'email' => $email,
    'telefono' => $telefono,
    'mensaje' => $mensaje,
    'items' => $items,
    'created_at' => date('Y-m-d H:i:s'),
    'estado' => 'pendiente'
];
$presupuestos[] = $presupuesto;
file_put_contents($dataFile, json_encode($presupuestos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Asignar precios estimados para el presupuesto (desde productos existentes o genéricos)
$itemsConPrecio = [];
foreach ($items as $item) {
    $precio = 0;
    $cat = strtolower($item['categoria'] ?? '');
    if (strpos($cat, 'electronica') !== false) $precio = 45000;
    elseif (strpos($cat, 'moda') !== false) $precio = 38000;
    elseif (strpos($cat, 'hogar') !== false) $precio = 18000;
    elseif (strpos($cat, 'deportes') !== false) $precio = 35000;
    else $precio = 25000;
    $itemsConPrecio[] = [
        'nombre' => $item['nombre'],
        'cantidad' => intval($item['cantidad'] ?? 1),
        'precio' => $precio,
        'categoria' => $item['categoria'] ?? ''
    ];
}

// Enviar email con presupuesto
$cliente = ['nombre' => $nombre, 'email' => $email];
enviarEmailTipo('presupuesto', $email, $pedido ?? [], $itemsConPrecio, [
    'presupuesto_id' => $presupuestoId,
    'cliente' => $cliente
]);

// También enviar al admin
enviarEmailTipo('presupuesto', 'proveedores@shoprive.com', $pedido ?? [], $itemsConPrecio, [
    'presupuesto_id' => $presupuestoId,
    'cliente' => $cliente
]);

echo json_encode([
    'success' => true,
    'presupuesto_id' => $presupuestoId,
    'message' => 'Presupuesto generado. Te enviaremos un email con los detalles.'
]);
