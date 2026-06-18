<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$configFile = __DIR__ . '/../data/configuracion.json';
$config = [];
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true) ?: [];
}

$defaults = [
    'envio_domicilio' => '1',
    'envio_retiro' => '1',
    'chatbot_activo' => '1',
    'contacto_email' => 'soporte@shoprive.com',
    'contacto_telefono' => '+54 11 5555-1234',
    'contacto_whatsapp' => '+541155551234',
    'contacto_direccion' => 'Av. Corrientes 1234, Buenos Aires, Argentina',
    'contacto_horario' => 'Lun a Vie 9:00 - 18:00',
    'contacto_maps' => 'https://www.google.com/maps/search/?api=1&query=Av.+Corrientes+1234,+Buenos+Aires,+Argentina',
    'redes_facebook' => 'https://facebook.com/shoprive',
    'redes_instagram' => 'https://instagram.com/shoprive',
    'redes_twitter' => 'https://twitter.com/shoprive',
];

foreach ($defaults as $k => $v) {
    if (!isset($config[$k])) $config[$k] = $v;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($defaults as $k => $v) {
        $config[$k] = $_POST[$k] ?? '0';
    }
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    $mensaje = 'Configuración guardada.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Configuración - Admin ShopRive</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .admin-layout { display: flex; min-height: 100vh; }
    .admin-sidebar { width: 260px; background: var(--bg-card); border-right: 1px solid var(--border); padding: 24px; flex-shrink: 0; }
    .admin-sidebar h2 { font-size: 1.2rem; margin-bottom: 24px; }
    .admin-sidebar a { display: block; padding: 12px 16px; color: var(--text-muted); text-decoration: none; border-radius: 12px; margin-bottom: 4px; }
    .admin-sidebar a:hover, .admin-sidebar a.active { background: var(--bg-card-hover); color: var(--text); }
    .admin-main { flex: 1; padding: 40px; min-width: 0; }
    .admin-main h1 { font-size: 2rem; margin-bottom: 8px; }
    .config-section { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 28px; margin-bottom: 24px; }
    .config-section h2 { font-size: 1.1rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .config-section h2 svg { width: 22px; height: 22px; opacity: 0.6; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted); }
    .form-group input, .form-group select { width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px; color: var(--text); outline: none; }
    .form-group input:focus { border-color: var(--primary); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .toggle-wrap { display: flex; align-items: center; gap: 12px; padding: 12px 0; }
    .toggle-wrap input[type="checkbox"] { width: 48px; height: 26px; appearance: none; background: var(--border); border-radius: 13px; position: relative; cursor: pointer; transition: 0.2s; flex-shrink: 0; }
    .toggle-wrap input[type="checkbox"]::before { content: ''; position: absolute; width: 22px; height: 22px; background: white; border-radius: 50%; top: 2px; left: 2px; transition: 0.2s; }
    .toggle-wrap input[type="checkbox"]:checked { background: var(--primary); }
    .toggle-wrap input[type="checkbox"]:checked::before { left: 24px; }
    .toggle-wrap .toggle-label { font-weight: 600; }
    .toggle-wrap .toggle-desc { font-size: 0.85rem; color: var(--text-muted); }
    .alert { background: rgba(0,184,148,0.15); color: var(--success); padding: 12px 20px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; }
    @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <h2>ShopRive Admin</h2>
      <a href="index.php">Dashboard</a>
      <a href="productos.php">Productos</a>
      <a href="pedidos.php">Pedidos</a>
      <a href="usuarios.php">Usuarios</a>
      <a href="chatbot.php">Chatbot</a>
      <a href="configuracion.php" class="active">Configuración</a>
      <a href="../auth/logout.php">Cerrar Sesión</a>
      <a href="../index.php" style="margin-top:20px;color:var(--accent);">← Tienda</a>
    </aside>
    <main class="admin-main">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <h1>Configuración</h1>
        <a href="../index.php" class="btn-primary" style="text-decoration:none;background:transparent;border:1px solid var(--border);font-size:0.9rem;padding:10px 20px;" target="_blank">Ver Tienda</a>
      </div>
      <p style="color:var(--text-muted);margin-bottom:28px;">Habilitá o deshabilitá funcionalidades de la tienda y editá la información de contacto.</p>

      <?php if (isset($mensaje)): ?><div class="alert"><?= $mensaje ?></div><?php endif; ?>

      <form method="POST">
        <div class="config-section">
          <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>Formas de Envío</h2>
          <div class="toggle-wrap">
            <input type="checkbox" id="envio_domicilio" name="envio_domicilio" value="1" <?= $config['envio_domicilio'] === '1' ? 'checked' : '' ?>>
            <div><div class="toggle-label">Envío a domicilio</div><div class="toggle-desc">Los clientes pueden elegir envío a su dirección</div></div>
          </div>
          <div class="toggle-wrap">
            <input type="checkbox" id="envio_retiro" name="envio_retiro" value="1" <?= $config['envio_retiro'] === '1' ? 'checked' : '' ?>>
            <div><div class="toggle-label">Retiro en local</div><div class="toggle-desc">Los clientes pueden retirar en Av. Corrientes 1234</div></div>
          </div>
        </div>

        <div class="config-section">
          <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>Chatbot</h2>
          <div class="toggle-wrap">
            <input type="checkbox" id="chatbot_activo" name="chatbot_activo" value="1" <?= $config['chatbot_activo'] === '1' ? 'checked' : '' ?>>
            <div><div class="toggle-label">Chatbot activo</div><div class="toggle-desc">Mostrar el asistente virtual en la tienda</div></div>
          </div>
        </div>

        <div class="config-section">
          <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>Información de Contacto</h2>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="contacto_email" value="<?= htmlspecialchars($config['contacto_email']) ?>">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Teléfono</label>
              <input type="text" name="contacto_telefono" value="<?= htmlspecialchars($config['contacto_telefono']) ?>">
            </div>
            <div class="form-group">
              <label>WhatsApp (número completo)</label>
              <input type="text" name="contacto_whatsapp" value="<?= htmlspecialchars($config['contacto_whatsapp']) ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Dirección del local</label>
            <input type="text" name="contacto_direccion" value="<?= htmlspecialchars($config['contacto_direccion']) ?>">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Horario de atención</label>
              <input type="text" name="contacto_horario" value="<?= htmlspecialchars($config['contacto_horario']) ?>">
            </div>
            <div class="form-group">
              <label>URL Google Maps</label>
              <input type="url" name="contacto_maps" value="<?= htmlspecialchars($config['contacto_maps']) ?>">
            </div>
          </div>
        </div>

        <div class="config-section">
          <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>Redes Sociales</h2>
          <div class="form-group">
            <label>Facebook URL</label>
            <input type="url" name="redes_facebook" value="<?= htmlspecialchars($config['redes_facebook']) ?>">
          </div>
          <div class="form-group">
            <label>Instagram URL</label>
            <input type="url" name="redes_instagram" value="<?= htmlspecialchars($config['redes_instagram']) ?>">
          </div>
          <div class="form-group">
            <label>X / Twitter URL</label>
            <input type="url" name="redes_twitter" value="<?= htmlspecialchars($config['redes_twitter']) ?>">
          </div>
        </div>

        <button class="btn-primary" type="submit">Guardar Configuración</button>
      </form>
    </main>
  </div>
</body>
</html>
