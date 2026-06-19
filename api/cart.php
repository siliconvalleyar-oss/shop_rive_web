<?php
/**
 * Cart API - CRUD operations
 *
 * Routes (via api/index.php):
 *   GET  /api/cart
 *   POST /api/cart/add
 *   POST /api/cart/update
 *   POST /api/cart/remove
 *   POST /api/cart/clear
 *
 * Legacy: cart.php?action=add|update|remove|clear|updateStock
 */

require_once __DIR__ . '/../lib/bootstrap.php';

// Handle legacy ?action= format
$action = $_GET['action'] ?? '';
if ($action && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
  switch ($action) {
    case 'add': handleAddToCart(); break;
    case 'update': handleUpdateCart(); break;
    case 'remove': handleRemoveFromCart(); break;
    case 'clear': handleClearCart(); break;
    case 'updateStock': handleUpdateStock(); break;
    default: handleGetCart(); break;
  }
}

function requireAuth(): void {
  if (!isset($_SESSION['user_id'])) {
    Response::error('Iniciá sesión primero', 401);
  }
}

/**
 * GET /api/cart
 */
function handleGetCart() {
  $userId = $_SESSION['user_id'] ?? null;
  if (!$userId) {
    Response::success(['items' => []]);
  }

  global $pdo;
  $stmt = $pdo->prepare("
    SELECT c.*, p.nombre, p.precio, p.color, p.riv_file
    FROM carrito c
    JOIN productos p ON c.producto_id = p.id
    WHERE c.usuario_id = ?
  ");
  $stmt->execute([$userId]);
  Response::success(['items' => $stmt->fetchAll()]);
}

/**
 * POST /api/cart/add
 */
function handleAddToCart() {
  requireAuth();
  $data = getJsonBody();

  $v = new Validator($data, [
    'producto_id' => 'Producto',
    'cantidad' => 'Cantidad'
  ]);
  $v->required('producto_id', 'cantidad')->numeric('producto_id')->numeric('cantidad');
  if (!$v->passes()) Response::error($v->firstError(), 422);

  $productoId = (int)$data['producto_id'];
  $cantidad = max(1, (int)($data['cantidad'] ?? 1));
  $userId = $_SESSION['user_id'];

  global $pdo;
  $stmt = $pdo->prepare("SELECT id FROM carrito WHERE usuario_id = ? AND producto_id = ?");
  $stmt->execute([$userId, $productoId]);

  if ($stmt->fetch()) {
    $stmt = $pdo->prepare("UPDATE carrito SET cantidad = cantidad + ? WHERE usuario_id = ? AND producto_id = ?");
    $stmt->execute([$cantidad, $userId, $productoId]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $productoId, $cantidad]);
  }

  Response::success([], 'Producto agregado al carrito');
}

/**
 * POST /api/cart/update
 */
function handleUpdateCart() {
  requireAuth();
  $data = getJsonBody();

  $v = new Validator($data, ['producto_id' => 'Producto', 'cantidad' => 'Cantidad']);
  $v->required('producto_id', 'cantidad')->numeric('producto_id')->numeric('cantidad');
  if (!$v->passes()) Response::error($v->firstError(), 422);

  global $pdo;
  $pdo->prepare("UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?")
    ->execute([(int)$data['cantidad'], $_SESSION['user_id'], (int)$data['producto_id']]);

  Response::success([], 'Carrito actualizado');
}

/**
 * POST /api/cart/remove
 */
function handleRemoveFromCart() {
  requireAuth();
  $data = getJsonBody();

  $productoId = (int)($data['producto_id'] ?? 0);
  if (!$productoId) Response::error('Producto requerido', 422);

  global $pdo;
  $pdo->prepare("DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?")
    ->execute([$_SESSION['user_id'], $productoId]);

  Response::success([], 'Producto eliminado del carrito');
}

/**
 * POST /api/cart/clear
 */
function handleClearCart() {
  requireAuth();
  global $pdo;
  $pdo->prepare("DELETE FROM carrito WHERE usuario_id = ?")->execute([$_SESSION['user_id']]);
  Response::success([], 'Carrito vaciado');
}

/**
 * POST /api/cart/updateStock
 */
function handleUpdateStock() {
  requireAuth();
  $data = getJsonBody();

  $v = new Validator($data, ['producto_id' => 'Producto', 'cantidad' => 'Cantidad']);
  $v->required('producto_id', 'cantidad');
  if (!$v->passes()) Response::error($v->firstError(), 422);

  global $pdo;
  $pdo->prepare("UPDATE productos SET stock = MAX(0, stock - ?) WHERE id = ?")
    ->execute([(int)$data['cantidad'], (int)$data['producto_id']]);

  Response::success([], 'Stock actualizado');
}
