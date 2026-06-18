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
    'brand_name' => 'ShopRive',
    'brand_logo' => '',
    'brand_icon' => '',
    'brand_bg' => '',
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
    'color_primary' => '#6c5ce7',
    'color_accent' => '#fd79a8',
    'color_bg' => '#0a0a1a',
    'color_bg_card' => '#12122a',
    'color_bg_card_hover' => '#1a1a3e',
    'color_text' => '#ffffff',
    'color_text_muted' => '#888888',
    'color_success' => '#00b894',
    'color_border' => '#2a2a4a',
    'font_family' => "'Segoe UI', system-ui, -apple-system, sans-serif",
    'font_heading' => "'Segoe UI', system-ui, -apple-system, sans-serif",
    'font_size_base' => '16px',
];

foreach ($defaults as $k => $v) {
    if (!isset($config[$k])) $config[$k] = $v;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($defaults as $k => $v) {
        if ($k === 'brand_logo' || $k === 'brand_icon') continue;
        $config[$k] = $_POST[$k] ?? '0';
    }
    // Handle file uploads
    $uploadDir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    foreach (['brand_logo' => 'logo', 'brand_icon' => 'icon', 'brand_bg' => 'bg'] as $field => $prefix) {
        if (!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'ico'])) {
                $name = $prefix . '-' . time() . '.' . $ext;
                move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . $name);
                $config[$field] = $name;
            }
        } elseif (isset($_POST[$field . '_keep'])) {
            // keep existing value
        } else {
            $config[$field] = $_POST[$field] ?? $config[$field] ?? '';
        }
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
  <?php require_once __DIR__ . '/../config/apariencia.php'; renderThemeStyles(); ?>
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
      <a href="categorias.php">Categorías</a>
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

      <form method="POST" enctype="multipart/form-data">
        <div class="config-section">
          <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>Marca</h2>
          <div class="form-group">
            <label>Nombre de la empresa / tienda</label>
            <input type="text" name="brand_name" value="<?= htmlspecialchars($config['brand_name']) ?>">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Logo (PNG, JPG, SVG)</label>
              <input type="hidden" name="brand_logo_keep" value="1">
              <input type="file" name="brand_logo" accept=".png,.jpg,.jpeg,.svg" style="color:var(--text);font-size:0.9rem;">
              <?php if (!empty($config['brand_logo'])): ?>
                <div style="margin-top:8px;display:flex;align-items:center;gap:12px;">
                  <img src="../assets/uploads/<?= htmlspecialchars($config['brand_logo']) ?>" style="max-width:80px;max-height:40px;border-radius:8px;">
                  <span style="font-size:0.8rem;color:var(--text-muted);"><?= $config['brand_logo'] ?></span>
                </div>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label>Icono / Favicon (PNG, ICO)</label>
              <input type="hidden" name="brand_icon_keep" value="1">
              <input type="file" name="brand_icon" accept=".png,.ico" style="color:var(--text);font-size:0.9rem;">
              <?php if (!empty($config['brand_icon'])): ?>
                <div style="margin-top:8px;display:flex;align-items:center;gap:12px;">
                  <img src="../assets/uploads/<?= htmlspecialchars($config['brand_icon']) ?>" style="max-width:32px;max-height:32px;border-radius:4px;">
                  <span style="font-size:0.8rem;color:var(--text-muted);"><?= $config['brand_icon'] ?></span>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="form-group">
            <label>Imagen de fondo decorativa (PNG, JPG) — opcional</label>
            <input type="hidden" name="brand_bg_keep" value="1">
            <input type="file" name="brand_bg" accept=".png,.jpg,.jpeg" style="color:var(--text);font-size:0.9rem;">
            <?php if (!empty($config['brand_bg'])): ?>
              <div style="margin-top:8px;display:flex;align-items:center;gap:12px;">
                <img src="../assets/uploads/<?= htmlspecialchars($config['brand_bg']) ?>" style="max-width:120px;max-height:60px;border-radius:8px;object-fit:cover;">
                <span style="font-size:0.8rem;color:var(--text-muted);"><?= $config['brand_bg'] ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>

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

        <div class="config-section">
          <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>Apariencia</h2>
          <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:16px;">Personalizá los colores y tipografía de la tienda. Los cambios se aplican en vivo.</p>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;">
            <div class="form-group">
              <label><span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:var(--primary);vertical-align:middle;margin-right:6px;"></span>Color primario</label>
              <input type="color" name="color_primary" value="<?= $config['color_primary'] ?>" style="height:48px;padding:4px;cursor:pointer;">
            </div>
            <div class="form-group">
              <label><span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:var(--accent);vertical-align:middle;margin-right:6px;"></span>Color de acento</label>
              <input type="color" name="color_accent" value="<?= $config['color_accent'] ?>" style="height:48px;padding:4px;cursor:pointer;">
            </div>
            <div class="form-group">
              <label><span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:var(--bg);vertical-align:middle;margin-right:6px;border:1px solid var(--border);"></span>Fondo</label>
              <input type="color" name="color_bg" value="<?= $config['color_bg'] ?>" style="height:48px;padding:4px;cursor:pointer;">
            </div>
            <div class="form-group">
              <label><span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:var(--bg-card);vertical-align:middle;margin-right:6px;border:1px solid var(--border);"></span>Fondo tarjetas</label>
              <input type="color" name="color_bg_card" value="<?= $config['color_bg_card'] ?>" style="height:48px;padding:4px;cursor:pointer;">
            </div>
            <div class="form-group">
              <label><span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:var(--bg-card-hover);vertical-align:middle;margin-right:6px;border:1px solid var(--border);"></span>Fondo tarjetas hover</label>
              <input type="color" name="color_bg_card_hover" value="<?= $config['color_bg_card_hover'] ?>" style="height:48px;padding:4px;cursor:pointer;">
            </div>
            <div class="form-group">
              <label><span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:var(--text);vertical-align:middle;margin-right:6px;border:1px solid var(--border);"></span>Texto</label>
              <input type="color" name="color_text" value="<?= $config['color_text'] ?>" style="height:48px;padding:4px;cursor:pointer;">
            </div>
            <div class="form-group">
              <label><span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:var(--text-muted);vertical-align:middle;margin-right:6px;border:1px solid var(--border);"></span>Texto secundario</label>
              <input type="color" name="color_text_muted" value="<?= $config['color_text_muted'] ?>" style="height:48px;padding:4px;cursor:pointer;">
            </div>
            <div class="form-group">
              <label><span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:var(--success);vertical-align:middle;margin-right:6px;"></span>Éxito</label>
              <input type="color" name="color_success" value="<?= $config['color_success'] ?>" style="height:48px;padding:4px;cursor:pointer;">
            </div>
            <div class="form-group">
              <label><span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:var(--border);vertical-align:middle;margin-right:6px;"></span>Bordes</label>
              <input type="color" name="color_border" value="<?= $config['color_border'] ?>" style="height:48px;padding:4px;cursor:pointer;">
            </div>
          </div>
          <div class="form-row" style="margin-top:16px;">
            <div class="form-group">
              <label>Fuente general</label>
              <select name="font_family" style="padding:12px;background:var(--bg);border:1px solid var(--border);border-radius:10px;color:var(--text);outline:none;width:100%;">
                <?php
                $fonts = [
                  "'Segoe UI', system-ui, -apple-system, sans-serif" => 'Sistema (Segoe UI)',
                  "'Inter', system-ui, sans-serif" => 'Inter',
                  "'Poppins', sans-serif" => 'Poppins',
                  "'Roboto', sans-serif" => 'Roboto',
                  "'Montserrat', sans-serif" => 'Montserrat',
                  "'Open Sans', sans-serif" => 'Open Sans',
                  "'Nunito', sans-serif" => 'Nunito',
                  "'Raleway', sans-serif" => 'Raleway',
                  "'DM Sans', sans-serif" => 'DM Sans',
                  "'Outfit', sans-serif" => 'Outfit',
                  "'Plus Jakarta Sans', sans-serif" => 'Plus Jakarta Sans',
                  "'Segoe UI', 'Roboto', 'Helvetica', Arial, sans-serif" => 'Segoe UI Stack',
                ];
                $current = $config['font_family'] ?? "'Segoe UI', system-ui, -apple-system, sans-serif";
                foreach ($fonts as $val => $label):
                  $sel = $val === $current ? 'selected' : '';
                ?>
                  <option value="<?= htmlspecialchars($val) ?>" <?= $sel ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Fuente de títulos</label>
              <select name="font_heading" style="padding:12px;background:var(--bg);border:1px solid var(--border);border-radius:10px;color:var(--text);outline:none;width:100%;">
                <?php
                $currentH = $config['font_heading'] ?? "'Segoe UI', system-ui, -apple-system, sans-serif";
                foreach ($fonts as $val => $label):
                  $sel = $val === $currentH ? 'selected' : '';
                ?>
                  <option value="<?= htmlspecialchars($val) ?>" <?= $sel ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group" style="margin-top:16px;">
            <label>Tamaño de fuente base</label>
            <select name="font_size_base" style="padding:12px;background:var(--bg);border:1px solid var(--border);border-radius:10px;color:var(--text);outline:none;width:100%;">
              <?php
              $sizes = ['14px', '15px', '16px', '17px', '18px', '20px'];
              $currentS = $config['font_size_base'] ?? '16px';
              foreach ($sizes as $s):
                $sel = $s === $currentS ? 'selected' : '';
              ?>
                <option value="<?= $s ?>" <?= $sel ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="margin-top:16px;padding:16px;background:var(--bg);border-radius:12px;border:1px solid var(--border);">
            <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:8px;">Vista previa</div>
            <div style="font-size:1.2rem;font-weight:700;color:var(--text);margin-bottom:4px;font-family:var(--font-heading);">Texto de ejemplo — Título</div>
            <div style="font-size:0.95rem;color:var(--text-muted);">Este es un párrafo de ejemplo con <span style="color:var(--primary);">color primario</span> y <span style="color:var(--accent);">color de acento</span>.</div>
            <div style="display:flex;gap:12px;margin-top:12px;">
              <span style="background:var(--primary);color:#fff;padding:6px 16px;border-radius:8px;font-size:0.85rem;">Botón primario</span>
              <span style="background:var(--accent);color:#fff;padding:6px 16px;border-radius:8px;font-size:0.85rem;">Botón acento</span>
              <span style="color:var(--success);font-size:0.85rem;">✓ Éxito</span>
            </div>
          </div>
        </div>

        <button class="btn-primary" type="submit">Guardar Configuración</button>
      </form>
    </main>
  </div>
</body>
</html>
