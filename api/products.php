<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/../config/database.php';

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, nombre, categoria, precio, riv_file, color, stock FROM productos ORDER BY id");
        $productos = $stmt->fetchAll();
        echo json_encode(['success' => true, 'productos' => $productos, 'source' => 'db']);
        exit;
    } catch (Exception $e) {}
}

$fallback = [
    ['id' => 1, 'nombre' => 'Auriculares Pro', 'categoria' => 'electronica', 'precio' => 45000, 'riv_file' => 'hero-ui-animation', 'color' => '#6c5ce7', 'stock' => 25],
    ['id' => 2, 'nombre' => 'Reloj Inteligente', 'categoria' => 'electronica', 'precio' => 65000, 'riv_file' => 'rotating-can', 'color' => '#fd79a8', 'stock' => 15],
    ['id' => 3, 'nombre' => 'Zapatillas Urbanas', 'categoria' => 'moda', 'precio' => 52000, 'riv_file' => 'shoe-showcase', 'color' => '#00b894', 'stock' => 30],
    ['id' => 4, 'nombre' => 'Bolso de Mano', 'categoria' => 'moda', 'precio' => 38000, 'riv_file' => 'purse-360', 'color' => '#fdcb6e', 'stock' => 20],
    ['id' => 5, 'nombre' => 'Lámpara LED', 'categoria' => 'hogar', 'precio' => 18000, 'riv_file' => 'off_road_car_0_6', 'color' => '#e17055', 'stock' => 50],
    ['id' => 6, 'nombre' => 'Campera Premium', 'categoria' => 'moda', 'precio' => 78000, 'riv_file' => 'shoe-showcase', 'color' => '#00cec9', 'stock' => 12],
    ['id' => 7, 'nombre' => 'Tablet 10"', 'categoria' => 'electronica', 'precio' => 120000, 'riv_file' => 'rotating-can', 'color' => '#a29bfe', 'stock' => 8],
    ['id' => 8, 'nombre' => 'Set de Pesas', 'categoria' => 'deportes', 'precio' => 35000, 'riv_file' => 'off_road_car_0_6', 'color' => '#fab1a0', 'stock' => 18],
    ['id' => 9, 'nombre' => 'Billetera Elegante', 'categoria' => 'moda', 'precio' => 22000, 'riv_file' => 'purse-360', 'color' => '#6c5ce7', 'stock' => 35],
    ['id' => 10, 'nombre' => 'Parlante Portátil', 'categoria' => 'electronica', 'precio' => 32000, 'riv_file' => 'hero-ui-animation', 'color' => '#fd79a8', 'stock' => 22],
];
echo json_encode(['success' => true, 'productos' => $fallback, 'source' => 'fallback']);
