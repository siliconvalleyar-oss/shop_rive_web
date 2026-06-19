<?php
require_once __DIR__ . '/../lib/SessionManager.php';
require_once __DIR__ . '/../lib/Logger.php';
Logger::init();
SessionManager::init();
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../config/database.php';

$user = null;
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $localidad = trim($_POST['localidad'] ?? '');

    if (!$nombre || !$email) {
        $error = 'Completá nombre y email';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user['id']]);
        if ($stmt->fetch()) {
            $error = 'El email ya está en uso por otro usuario';
        } else {
            if ($password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, email=?, password=?, telefono=?, direccion=?, localidad=? WHERE id=?");
                $stmt->execute([$nombre, $email, $hash, $telefono, $direccion, $localidad, $user['id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, email=?, telefono=?, direccion=?, localidad=? WHERE id=?");
                $stmt->execute([$nombre, $email, $telefono, $direccion, $localidad, $user['id']]);
            }
            $_SESSION['user_nombre'] = $nombre;
            $mensaje = 'Datos actualizados';
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi Perfil - ShopRive</title>
  <link rel="stylesheet" href="../css/style.css">
  <?php require_once __DIR__ . '/../config/apariencia.php'; renderThemeStyles(); ?>
  <style>
    .auth-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; background: var(--bg); }
    .auth-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 24px; padding: 48px; width: 100%; max-width: 480px; animation: fadeInUp 0.5s ease; }
    .auth-card h1 { font-size: 1.8rem; margin-bottom: 8px; }
    .auth-card p { color: var(--text-muted); margin-bottom: 32px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 8px; color: var(--text-muted); }
    .form-group input { width: 100%; padding: 14px 16px; background: var(--bg); border: 1px solid var(--border); border-radius: 12px; color: var(--text); font-size: 1rem; outline: none; }
    .form-group input:focus { border-color: var(--primary); }
    .alert-success { background: rgba(0,184,148,0.15); color: var(--success); padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; }
    .alert-error { background: rgba(253,121,168,0.1); border: 1px solid var(--accent); color: var(--accent); padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; }
    .avatar-large { width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--accent)); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; margin: 0 auto 16px; }
    .auth-link { text-align: center; margin-top: 24px; color: var(--text-muted); }
    .auth-link a { color: var(--primary); text-decoration: none; font-weight: 600; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  </style>
</head>
<body class="auth-page">
  <div class="auth-card">
    <div class="avatar-large"><?= strtoupper(substr($user['nombre'] ?? '?', 0, 1)) ?></div>
    <h1 style="text-align:center;">Mi Perfil</h1>
    <p style="text-align:center;">Editá tus datos personales</p>

    <?php if ($mensaje): ?><div class="alert-success"><?= $mensaje ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-error"><?= $error ?></div><?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Nombre</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($user['nombre'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Nueva contraseña (dejá vacío para mantener la actual)</label>
        <input type="password" name="password" placeholder="••••••••">
      </div>
      <div class="form-group">
        <label>Teléfono</label>
        <input type="text" name="telefono" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>" placeholder="+54 11 5555-1234">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Dirección</label>
          <input type="text" name="direccion" value="<?= htmlspecialchars($user['direccion'] ?? '') ?>" placeholder="Calle y número">
        </div>
        <div class="form-group">
          <label>Localidad</label>
          <input type="text" name="localidad" value="<?= htmlspecialchars($user['localidad'] ?? '') ?>" placeholder="Ciudad">
        </div>
      </div>
      <button type="submit" class="btn-primary" style="width:100%;">Guardar Cambios</button>
    </form>
    <div class="auth-link">
      <a href="../index.php">← Volver a la tienda</a>
    </div>
  </div>
</body>
</html>
