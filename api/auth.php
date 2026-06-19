<?php
/**
 * Auth API - Login, Register, Session, Logout
 *
 * Routes (via api/index.php):
 *   POST /api/auth/register
 *   POST /api/auth/login
 *   GET  /api/auth/session
 *   POST /api/auth/logout
 *
 * Legacy: auth.php?action=register|login|session|logout
 */

require_once __DIR__ . '/../lib/bootstrap.php';

// Handle legacy ?action= format
$action = $_GET['action'] ?? '';
if ($action && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
  switch ($action) {
    case 'register': handleRegister(); break;
    case 'login': handleLogin(); break;
    case 'session': handleSession(); break;
    case 'logout': handleLogout(); break;
    default: Response::error('Acción no válida');
  }
}

/**
 * POST /api/auth/register
 */
function handleRegister() {
  $data = getJsonBody();

  $v = new Validator($data, [
    'nombre' => 'Nombre',
    'email' => 'Email',
    'password' => 'Contraseña'
  ]);
  $v->required('nombre', 'email', 'password')
    ->email('email')
    ->minLength('nombre', 2)
    ->maxLength('nombre', 100)
    ->minLength('password', 4);

  if (!$v->passes()) {
    Response::error($v->firstError(), 422);
  }

  $nombre = trim($data['nombre']);
  $email = trim($data['email']);
  $password = $data['password'];

  global $pdo;
  $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
  $stmt->execute([$email]);
  if ($stmt->fetch()) {
    Response::error('El email ya está registrado', 409);
  }

  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, 'usuario')");
  $stmt->execute([$nombre, $email, $hash]);

  $userId = $pdo->lastInsertId();
  $_SESSION['user_id'] = $userId;
  $_SESSION['user_nombre'] = $nombre;
  $_SESSION['user_rol'] = 'usuario';

  Logger::info("Usuario registrado: $email (ID: $userId)");
  CSRF::refresh();

  Response::success([
    'user' => [
      'id' => $userId,
      'nombre' => $nombre,
      'rol' => 'usuario'
    ]
  ], 'Registro exitoso');
}

/**
 * POST /api/auth/login
 */
function handleLogin() {
  $data = getJsonBody();

  $v = new Validator($data, [
    'email' => 'Email',
    'password' => 'Contraseña'
  ]);
  $v->required('email', 'password')->email('email');

  if (!$v->passes()) {
    Response::error($v->firstError(), 422);
  }

  $email = trim($data['email']);
  $password = $data['password'];

  global $pdo;
  $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if (!$user || !password_verify($password, $user['password'])) {
    Logger::warning("Intento de login fallido: $email");
    Response::error('Email o contraseña incorrectos', 401);
  }

  $_SESSION['user_id'] = $user['id'];
  $_SESSION['user_nombre'] = $user['nombre'];
  $_SESSION['user_rol'] = $user['rol'];

  Logger::info("Login exitoso: $email (ID: {$user['id']})");
  CSRF::refresh();

  Response::success([
    'user' => [
      'id' => $user['id'],
      'nombre' => $user['nombre'],
      'rol' => $user['rol']
    ]
  ], 'Inicio de sesión exitoso');
}

/**
 * GET /api/auth/session
 */
function handleSession() {
  if (isset($_SESSION['user_id'])) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, nombre, email, rol, telefono, direccion, localidad FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
      Response::success(['user' => $user]);
    } else {
      Response::success([
        'user' => [
          'id' => $_SESSION['user_id'],
          'nombre' => $_SESSION['user_nombre'],
          'rol' => $_SESSION['user_rol']
        ]
      ]);
    }
  } else {
    Response::success(['user' => null]);
  }
}

/**
 * POST /api/auth/logout
 */
function handleLogout() {
  session_destroy();
  Logger::info('Sesión cerrada');
  Response::success([], 'Sesión cerrada');
}
