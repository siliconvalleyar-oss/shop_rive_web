<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';

if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos. ¿Ejecutaste php scripts/setup_db.php?']);
    exit;
}

switch ($action) {
    case 'register':
        $data = json_decode(file_get_contents('php://input'), true);
        $nombre = trim($data['nombre'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!$nombre || !$email || !$password) {
            echo json_encode(['success' => false, 'error' => 'Completá todos los campos']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'El email ya está registrado']);
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, 'usuario')");
        $stmt->execute([$nombre, $email, $hash]);

        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['user_nombre'] = $nombre;
        $_SESSION['user_rol'] = 'usuario';

        echo json_encode(['success' => true, 'user' => [
            'id' => $_SESSION['user_id'],
            'nombre' => $nombre,
            'rol' => 'usuario'
        ]]);
        break;

    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'error' => 'Email o contraseña incorrectos']);
            exit;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['user_rol'] = $user['rol'];

        echo json_encode(['success' => true, 'user' => [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'rol' => $user['rol']
        ]]);
        break;

    case 'session':
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT id, nombre, email, rol, telefono, direccion, localidad FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if ($user) {
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                echo json_encode(['success' => true, 'user' => ['id' => $_SESSION['user_id'], 'nombre' => $_SESSION['user_nombre'], 'rol' => $_SESSION['user_rol']]]);
            }
        } else {
            echo json_encode(['success' => true, 'user' => null]);
        }
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}
