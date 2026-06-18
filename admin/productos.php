<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$uploadDir = __DIR__ . '/../assets/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

function handleFileUpload($field, $uploadDir) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/png', 'image/jpeg', 'image/svg+xml', 'application/octet-stream', 'image/pjpeg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES[$field]['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed) && !str_ends_with(strtolower($_FILES[$field]['name']), '.riv')) return null;
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES[$field]['name']);
    move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . $name);
    return $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $uploaded = handleFileUpload('archivo_imagen', $uploadDir);
        $archivo = $uploaded ?: ($_POST['riv_file'] ?: 'car');
        $soloRetiro = isset($_POST['solo_retiro']) ? 1 : 0;
        $stmt = $pdo->prepare("INSERT INTO productos (nombre, categoria, precio, stock, riv_file, color, solo_retiro) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['nombre'], $_POST['categoria'], $_POST['precio'], $_POST['stock'] ?: 0, $archivo, $_POST['color'], $soloRetiro]);
    } elseif ($action === 'edit' && isset($_POST['id'])) {
        if ($_FILES['archivo_imagen']['error'] === UPLOAD_ERR_OK) {
            $uploaded = handleFileUpload('archivo_imagen', $uploadDir);
            $archivo = $uploaded ?: ($_POST['riv_file'] ?: 'car');
        } else {
            $archivo = $_POST['riv_file'] ?: 'car';
        }
        $soloRetiro = isset($_POST['solo_retiro']) ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE productos SET nombre=?, categoria=?, precio=?, stock=?, riv_file=?, color=?, solo_retiro=? WHERE id=?");
        $stmt->execute([$_POST['nombre'], $_POST['categoria'], $_POST['precio'], $_POST['stock'] ?: 0, $archivo, $_POST['color'], $soloRetiro, $_POST['id']]);
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $pdo->prepare("DELETE FROM productos WHERE id = ?")->execute([$_POST['id']]);
    }
    header('Location: productos.php');
    exit;
}

$productos = $pdo->query("SELECT * FROM productos ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Productos - Admin ShopRive</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .admin-layout { display: flex; min-height: 100vh; }
    .admin-sidebar { width: 260px; background: var(--bg-card); border-right: 1px solid var(--border); padding: 24px; flex-shrink: 0; }
    .admin-sidebar h2 { font-size: 1.2rem; margin-bottom: 24px; }
    .admin-sidebar a { display: block; padding: 12px 16px; color: var(--text-muted); text-decoration: none; border-radius: 12px; margin-bottom: 4px; transition: all 0.2s; }
    .admin-sidebar a:hover, .admin-sidebar a.active { background: var(--bg-card-hover); color: var(--text); }
    .admin-main { flex: 1; padding: 40px; min-width: 0; }
    .admin-main h1 { font-size: 2rem; margin-bottom: 32px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
    th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border); white-space: nowrap; }
    th { color: var(--text-muted); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .color-dot { display: inline-block; width: 16px; height: 16px; border-radius: 50%; vertical-align: middle; margin-right: 4px; }
    .form-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 32px; max-width: 500px; }
    .form-card h2 { margin-bottom: 20px; font-size: 1.2rem; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted); }
    .form-group input, .form-group select { width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px; color: var(--text); outline: none; }
    .form-group input:focus, .form-group select:focus { border-color: var(--primary); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .btn-sm { padding: 6px 14px; border-radius: 8px; font-size: 0.85rem; cursor: pointer; border: none; font-weight: 600; }
    .btn-edit { background: rgba(108,92,231,0.15); color: var(--primary); border: 1px solid var(--primary); }
    .btn-edit:hover { background: var(--primary); color: white; }
    .btn-delete { background: transparent; border: 1px solid var(--accent); color: var(--accent); }
    .btn-delete:hover { background: var(--accent); color: white; }
    .stock-low { color: var(--accent); font-weight: 600; }
    .stock-ok { color: var(--success); }
    .actions { display: flex; gap: 6px; }
    /* Modal */
    .modal-overlay { display: none; position: fixed; inset:0; background: rgba(0,0,0,0.6); z-index:200; align-items:center; justify-content:center; }
    .modal-overlay.open { display: flex; }
    .modal-card { background: var(--bg-card); border:1px solid var(--border); border-radius:20px; padding:32px; width:90%; max-width:520px; max-height:90vh; overflow-y:auto; }
    .modal-card h2 { margin-bottom: 20px; }
    .tabs { display: flex; gap: 4px; margin-bottom: 24px; }
    .tab-btn { padding: 10px 20px; border-radius: 10px; border: none; background: var(--bg); color: var(--text-muted); cursor: pointer; font-weight: 600; font-size: 0.9rem; }
    .tab-btn.active { background: var(--primary); color: white; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
  </style>
</head>
<body>
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <h2>ShopRive Admin</h2>
      <a href="index.php">Dashboard</a>
      <a href="productos.php" class="active">Productos</a>
      <a href="categorias.php">Categorías</a>
      <a href="pedidos.php">Pedidos</a>
      <a href="usuarios.php">Usuarios</a>
      <a href="../auth/logout.php">Cerrar Sesión</a>
      <a href="../index.php" style="margin-top:20px;color:var(--accent);">← Tienda</a>
    </aside>
    <main class="admin-main">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <h1 style="margin-bottom:0;">Productos</h1>
        <div style="display:flex;gap:8px;">
          <a href="../index.php#productos" class="btn-primary" style="text-decoration:none;background:transparent;border:1px solid var(--border);font-size:0.9rem;padding:10px 20px;" target="_blank">Ver Galería</a>
          <button class="btn-primary" onclick="document.getElementById('add-overlay').classList.add('open')">+ Nuevo</button>
        </div>
      </div>
      <table>
        <tr>
          <th>Archivo</th><th>ID</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Retiro</th><th>Acción</th>
        </tr>
        <?php foreach ($productos as $p):
          $archivo = $p['riv_file'] ?? '';
          $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
          $imgExts = ['png', 'jpg', 'jpeg', 'svg'];
          $isImg = in_array($ext, $imgExts);
          $thumbUrl = '../assets/uploads/' . $archivo;
        ?>
        <tr>
          <td>
            <?php if ($isImg && file_exists(__DIR__ . '/../assets/uploads/' . $archivo)): ?>
              <img src="<?= $thumbUrl ?>" style="width:48px;height:48px;border-radius:8px;object-fit:cover;display:block;">
            <?php elseif ($archivo && $archivo !== 'car'): ?>
              <span style="font-size:0.75rem;color:var(--text-muted);"><?= $archivo ?></span>
            <?php else: ?>
              <span style="font-size:0.75rem;color:var(--text-muted);">—</span>
            <?php endif; ?>
          </td>
          <td><?= $p['id'] ?></td>
          <td><?= htmlspecialchars($p['nombre']) ?></td>
          <td><?= $p['categoria'] ?></td>
          <td>$<?= number_format($p['precio'], 0, ',', '.') ?></td>
          <td class="<?= ($p['stock'] ?? 0) <= 5 ? 'stock-low' : 'stock-ok' ?>"><?= (int)($p['stock'] ?? 0) ?></td>
          <td><?= !empty($p['solo_retiro']) ? '🏪 Sí' : '—' ?></td>
          <td>
            <div class="actions">
              <button class="btn-sm btn-edit" onclick="editProduct(<?= htmlspecialchars(json_encode($p)) ?>)">Editar</button>
              <form method="POST" onsubmit="return confirm('¿Eliminar <?= htmlspecialchars($p['nombre']) ?>?')">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <input type="hidden" name="action" value="delete">
                <button class="btn-sm btn-delete">Eliminar</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>

    </main>
  </div>

  <!-- EDIT MODAL -->
  <div class="modal-overlay" id="edit-overlay" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-card">
      <h2>Editar Producto</h2>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit-id">
        <div class="form-group">
          <label>Nombre</label>
          <input type="text" name="nombre" id="edit-nombre" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Categoría</label>
            <select name="categoria" id="edit-categoria">
              <option value="electronica">Electrónica</option>
              <option value="moda">Moda</option>
              <option value="hogar">Hogar</option>
              <option value="deportes">Deportes</option>
            </select>
          </div>
          <div class="form-group">
            <label>Precio ($)</label>
            <input type="number" name="precio" id="edit-precio" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Stock</label>
            <input type="number" name="stock" id="edit-stock" required>
          </div>
          <div class="form-group">
            <label>Archivo (.riv, .png, .jpg, .svg)</label>
            <input type="text" name="riv_file" id="edit-riv" placeholder="ej: hero-ui-animation (sin extensión)">
          </div>
        </div>
        <div class="form-group">
          <label>O subir archivo</label>
          <input type="file" name="archivo_imagen" accept=".riv,.png,.jpg,.jpeg,.svg" style="color:var(--text);font-size:0.9rem;">
          <div id="edit-preview" style="margin-top:8px;display:none;"></div>
        </div>
        <div class="form-group">
          <label>Color</label>
          <input type="color" name="color" id="edit-color">
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:12px;padding:12px 0;">
          <label style="margin:0;cursor:pointer;display:flex;align-items:center;gap:10px;">
            <input type="checkbox" name="solo_retiro" id="edit-solo-retiro" value="1" style="width:20px;height:20px;accent-color:var(--primary);cursor:pointer;">
            <span style="font-weight:600;font-size:0.95rem;">Solo retiro en local</span>
          </label>
          <span style="font-size:0.8rem;color:var(--text-muted);">El cliente debe retirar en el local (no se puede enviar)</span>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
          <button class="btn-primary" type="submit">Guardar Cambios</button>
          <button class="btn-primary" type="button" onclick="document.getElementById('edit-overlay').classList.remove('open')" style="background:transparent;border:1px solid var(--border);color:var(--text-muted);">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ADD MODAL -->
  <div class="modal-overlay" id="add-overlay" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-card">
      <h2>Nuevo Producto</h2>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
          <label>Nombre</label>
          <input type="text" name="nombre" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Categoría</label>
            <select name="categoria">
              <option value="electronica">Electrónica</option>
              <option value="moda">Moda</option>
              <option value="hogar">Hogar</option>
              <option value="deportes">Deportes</option>
            </select>
          </div>
          <div class="form-group">
            <label>Precio ($)</label>
            <input type="number" name="precio" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Stock</label>
            <input type="number" name="stock" value="10" required>
          </div>
          <div class="form-group">
            <label>Archivo (.riv, .png, .jpg, .svg)</label>
            <input type="text" name="riv_file" placeholder="ej: hero-ui-animation (sin extensión)">
          </div>
        </div>
        <div class="form-group">
          <label>O subir archivo</label>
          <input type="file" name="archivo_imagen" accept=".riv,.png,.jpg,.jpeg,.svg" style="color:var(--text);font-size:0.9rem;">
        </div>
        <div class="form-group">
          <label>Color</label>
          <input type="color" name="color" value="#6c5ce7">
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:12px;padding:12px 0;">
          <label style="margin:0;cursor:pointer;display:flex;align-items:center;gap:10px;">
            <input type="checkbox" name="solo_retiro" value="1" style="width:20px;height:20px;accent-color:var(--primary);cursor:pointer;">
            <span style="font-weight:600;font-size:0.95rem;">Solo retiro en local</span>
          </label>
          <span style="font-size:0.8rem;color:var(--text-muted);">El cliente debe retirar en el local (no se puede enviar)</span>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
          <button class="btn-primary" type="submit">Agregar</button>
          <button class="btn-primary" type="button" onclick="document.getElementById('add-overlay').classList.remove('open')" style="background:transparent;border:1px solid var(--border);color:var(--text-muted);">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function editProduct(p) {
      document.getElementById('edit-id').value = p.id;
      document.getElementById('edit-nombre').value = p.nombre;
      document.getElementById('edit-categoria').value = p.categoria;
      document.getElementById('edit-precio').value = p.precio;
      document.getElementById('edit-stock').value = p.stock || 0;
      document.getElementById('edit-riv').value = p.riv_file || '';
      document.getElementById('edit-color').value = p.color || '#6c5ce7';
      document.getElementById('edit-solo-retiro').checked = p.solo_retiro == 1;
      // Preview
      const preview = document.getElementById('edit-preview');
      const f = p.riv_file || '';
      const ext = f.split('.').pop().toLowerCase();
      if (['png','jpg','jpeg','svg'].includes(ext)) {
        preview.innerHTML = '<img src="../assets/uploads/' + f + '" style="max-width:120px;max-height:80px;border-radius:8px;border:1px solid var(--border);">';
        preview.style.display = 'block';
      } else {
        preview.style.display = 'none';
      }
      document.getElementById('edit-overlay').classList.add('open');
    }
  </script>
</body>
</html>
