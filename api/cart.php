<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'] ?? null;
$action = $_GET['action'] ?? '';

if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

switch ($action) {
    case 'add':
        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'Iniciá sesión primero']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $productoId = (int)($data['producto_id'] ?? 0);
        $cantidad = (int)($data['cantidad'] ?? 1);

        $stmt = $pdo->prepare("SELECT id FROM carrito WHERE usuario_id = ? AND producto_id = ?");
        $stmt->execute([$userId, $productoId]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE carrito SET cantidad = cantidad + ? WHERE usuario_id = ? AND producto_id = ?");
            $stmt->execute([$cantidad, $userId, $productoId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $productoId, $cantidad]);
        }
        echo json_encode(['success' => true]);
        break;

    case 'update':
        if (!$userId) { echo json_encode(['success' => false]); exit; }
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?");
        $stmt->execute([(int)$data['cantidad'], $userId, (int)$data['producto_id']]);
        echo json_encode(['success' => true]);
        break;

    case 'remove':
        if (!$userId) { echo json_encode(['success' => false]); exit; }
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?");
        $stmt->execute([$userId, (int)$data['producto_id']]);
        echo json_encode(['success' => true]);
        break;

    case 'updateStock':
        if (!$userId) { echo json_encode(['success' => false]); exit; }
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("UPDATE productos SET stock = MAX(0, stock - ?) WHERE id = ?");
        $stmt->execute([(int)$data['cantidad'], (int)$data['producto_id']]);
        echo json_encode(['success' => true]);
        break;

    case 'clear':
        if (!$userId) { echo json_encode(['success' => false]); exit; }
        $pdo->prepare("DELETE FROM carrito WHERE usuario_id = ?")->execute([$userId]);
        echo json_encode(['success' => true]);
        break;

    default:
        if (!$userId) {
            echo json_encode(['success' => true, 'items' => []]);
            exit;
        }
        $stmt = $pdo->prepare("
            SELECT c.*, p.nombre, p.precio, p.color, p.riv_file
            FROM carrito c
            JOIN productos p ON c.producto_id = p.id
            WHERE c.usuario_id = ?
        ");
        $stmt->execute([$userId]);
        echo json_encode(['success' => true, 'items' => $stmt->fetchAll()]);
}
