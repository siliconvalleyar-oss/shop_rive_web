<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$pedido_id = intval($_GET['id'] ?? 0);
if (!$pedido_id) { die('Pedido no especificado'); }

$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->execute([$pedido_id]);
$pedido = $stmt->fetch();
if (!$pedido) { die('Pedido no encontrado'); }

$stmtItems = $pdo->prepare("SELECT * FROM detalle_pedido WHERE pedido_id = ?");
$stmtItems->execute([$pedido_id]);
$items = $stmtItems->fetchAll();

// Generar o recuperar datos de facturación
$dataFile = __DIR__ . '/../data/facturacion.json';
$factData = file_exists($dataFile) ? (json_decode(file_get_contents($dataFile), true) ?: []) : [];

$pv = '0001'; // Punto de venta fijo
if (!isset($factData['ultimo_numero'])) $factData['ultimo_numero'] = 0;

// Si el pedido ya tiene factura, usarla; sino generar una nueva
if (empty($pedido['numero_factura'])) {
    $factData['ultimo_numero']++;
    $numero = str_pad($factData['ultimo_numero'], 8, '0', STR_PAD_LEFT);
    $pedido['numero_factura'] = $pv . '-' . $numero;
    $pedido['cae'] = str_pad(random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $pedido['cae_vencimiento'] = date('d/m/Y', strtotime('+30 days'));
    $pedido['fecha_factura'] = date('d/m/Y');
    // Guardar en pedido
    $pdo->prepare("UPDATE pedidos SET numero_factura=?, cae=?, cae_vencimiento=?, fecha_factura=? WHERE id=?")->execute([
        $pedido['numero_factura'], $pedido['cae'], $pedido['cae_vencimiento'], $pedido['fecha_factura'], $pedido_id
    ]);
    file_put_contents($dataFile, json_encode($factData));
} else {
    $numero = explode('-', $pedido['numero_factura'])[1] ?? '00000000';
}

$total = floatval($pedido['total']);
$iva = round($total * 21 / 121, 2); // IVA 21% incluido
$subtotal = $total - $iva;
$metodoLabels = ['tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'qr' => 'Cuenta DNI', 'mercadopago' => 'Mercado Pago', 'efectivo' => 'Efectivo'];
$envioLabels = ['domicilio' => 'Envío a domicilio', 'retiro' => 'Retiro en local'];
$tipoFactura = $pedido['tipo_envio'] === 'retiro' ? 'Factura B' : 'Factura A';

// Datos del emisor (simulados)
$emisor = [
    'razon_social' => 'SHOPRIVE S.A.',
    'cuit' => '30-71234567-8',
    'ingresos_brutos' => '123456789',
    'inicio_actividades' => '01/01/2024',
    'domicilio' => 'Av. Corrientes 1234, Buenos Aires',
    'condicion_iva' => 'Responsable Inscripto'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Factura <?= $pedido['numero_factura'] ?> - ShopRive</title>
  <style>
    @page { margin: 15mm; size: A4; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1a1a2e; background: #f0f0f5; padding: 20px; }
    .invoice-wrap { max-width: 210mm; margin: 0 auto; background: #fff; border-radius: 16px; padding: 40px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 3px solid #6c5ce7; }
    .header-left h1 { font-size: 28px; color: #6c5ce7; margin-bottom: 4px; }
    .header-left p { color: #888; font-size: 11px; }
    .header-right { text-align: right; }
    .header-right .factura-tipo { font-size: 22px; font-weight: 800; color: #6c5ce7; }
    .header-right .factura-num { font-size: 14px; color: #333; margin-top: 4px; }
    .badge-cae { display: inline-block; background: #6c5ce7; color: #fff; padding: 4px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-top: 8px; letter-spacing: 0.5px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }
    .info-box { background: #f8f8ff; border-radius: 12px; padding: 20px; }
    .info-box h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #6c5ce7; margin-bottom: 12px; }
    .info-box p { font-size: 12px; line-height: 1.6; color: #333; }
    .info-box .label { color: #888; font-size: 10px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    th { background: #6c5ce7; color: #fff; padding: 10px 14px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
    th:last-child { text-align: right; }
    th:nth-child(2), th:nth-child(3) { text-align: center; }
    td { padding: 10px 14px; border-bottom: 1px solid #eee; font-size: 12px; }
    td:last-child { text-align: right; font-weight: 600; }
    td:nth-child(2), td:nth-child(3) { text-align: center; }
    tfoot td { border-bottom: none; padding: 6px 14px; }
    .total-row td { font-weight: 700; font-size: 14px; color: #6c5ce7; border-top: 2px solid #6c5ce7; padding-top: 12px; }
    .iva-breakdown { background: #f8f8ff; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
    .iva-breakdown h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #6c5ce7; margin-bottom: 12px; }
    .iva-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; }
    .iva-row.total { font-weight: 700; border-top: 1px solid #ddd; padding-top: 8px; margin-top: 8px; color: #6c5ce7; }
    .footer { margin-top: 28px; padding-top: 20px; border-top: 2px solid #eee; display: flex; justify-content: space-between; align-items: end; gap: 24px; }
    .footer-left { font-size: 10px; color: #888; line-height: 1.6; }
    .footer-right { text-align: right; }
    .qr-placeholder { width: 120px; height: 120px; background: #fff; border: 2px solid #333; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #888; text-align: center; padding: 4px; }
    .qr-placeholder img { width: 120px; height: 120px; }
    .actions { text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid #ddd; }
    .actions button { padding: 12px 32px; border-radius: 50px; border: none; cursor: pointer; font-weight: 600; font-size: 14px; margin: 0 8px; }
    .btn-print { background: #6c5ce7; color: #fff; }
    .btn-back { background: #eee; color: #333; }
    .actions button:hover { opacity: 0.9; }
    @media print {
      body { background: #fff; padding: 0; }
      .invoice-wrap { box-shadow: none; border-radius: 0; padding: 20px; }
      .actions { display: none; }
      .no-print { display: none; }
      @page { margin: 10mm; }
    }
    .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); font-size: 80px; font-weight: 900; color: rgba(108,92,231,0.06); pointer-events: none; z-index: 0; letter-spacing: 10px; white-space: nowrap; }
  </style>
</head>
<body>
  <div class="watermark">FACTURA ELECTRÓNICA</div>
  <div class="invoice-wrap">
    <div class="header">
      <div class="header-left">
        <h1>ShopRive</h1>
        <p><?= $emisor['razon_social'] ?> · CUIT <?= $emisor['cuit'] ?></p>
        <p><?= $emisor['domicilio'] ?></p>
        <p>IIBB: <?= $emisor['ingresos_brutos'] ?> · Inicio: <?= $emisor['inicio_actividades'] ?></p>
      </div>
      <div class="header-right">
        <div class="factura-tipo"><?= $tipoFactura ?></div>
        <div class="factura-num">Nº <?= $pedido['numero_factura'] ?></div>
        <div class="badge-cae">CAE: <?= $pedido['cae'] ?></div>
        <div style="margin-top:6px;font-size:10px;color:#888;">Vto. CAE: <?= $pedido['cae_vencimiento'] ?></div>
      </div>
    </div>

    <div class="grid-2">
      <div class="info-box">
        <h3>Emisor</h3>
        <p>
          <span class="label">Razón Social:</span> <?= $emisor['razon_social'] ?><br>
          <span class="label">CUIT:</span> <?= $emisor['cuit'] ?><br>
          <span class="label">Condición IVA:</span> <?= $emisor['condicion_iva'] ?><br>
          <span class="label">Domicilio:</span> <?= $emisor['domicilio'] ?>
        </p>
      </div>
      <div class="info-box">
        <h3>Cliente</h3>
        <p>
          <span class="label">Nombre:</span> <?= htmlspecialchars($pedido['nombre']) ?><br>
          <span class="label">Email:</span> <?= htmlspecialchars($pedido['email']) ?><br>
          <span class="label">Teléfono:</span> <?= htmlspecialchars($pedido['telefono']) ?><br>
          <span class="label">CUIT/DNI:</span> —<br>
          <span class="label">Dirección:</span> <?= htmlspecialchars($pedido['direccion']) ?><?= !empty($pedido['localidad']) ? ', ' . htmlspecialchars($pedido['localidad']) : '' ?>
        </p>
      </div>
    </div>

    <table>
      <tr>
        <th style="width:50%;">Producto</th>
        <th style="width:12%;">Cant.</th>
        <th style="width:18%;">P. Unit.</th>
        <th style="width:20%;">Subtotal</th>
      </tr>
      <?php foreach ($items as $item):
        $subtotal_item = floatval($item['precio']) * intval($item['cantidad']);
      ?>
      <tr>
        <td><?= htmlspecialchars($item['nombre']) ?></td>
        <td style="text-align:center;"><?= (int)$item['cantidad'] ?></td>
        <td style="text-align:center;">$<?= number_format(floatval($item['precio']), 2, ',', '.') ?></td>
        <td>$<?= number_format($subtotal_item, 2, ',', '.') ?></td>
      </tr>
      <?php endforeach; ?>
    </table>

    <div style="display:flex;justify-content:space-between;gap:24px;margin-bottom:24px;">
      <div class="iva-breakdown" style="flex:1;">
        <h3>Detalle de IVA</h3>
        <div class="iva-row"><span>Alicuota 21%</span><span>$<?= number_format($iva, 2, ',', '.') ?></span></div>
        <div class="iva-row total"><span>Total IVA</span><span>$<?= number_format($iva, 2, ',', '.') ?></span></div>
      </div>
      <div style="min-width:200px;text-align:right;">
        <table style="margin-bottom:0;">
          <tr><td style="border:none;padding:4px 0;color:#888;">Subtotal</td><td style="border:none;padding:4px 0;text-align:right;">$<?= number_format($subtotal, 2, ',', '.') ?></td></tr>
          <tr><td style="border:none;padding:4px 0;color:#888;">IVA 21%</td><td style="border:none;padding:4px 0;text-align:right;">$<?= number_format($iva, 2, ',', '.') ?></td></tr>
          <tr class="total-row"><td style="border-top:2px solid #6c5ce7;padding-top:8px;font-weight:700;border-bottom:none;font-size:14px;">Total</td><td style="border-top:2px solid #6c5ce7;padding-top:8px;font-weight:700;border-bottom:none;font-size:14px;color:#6c5ce7;text-align:right;">$<?= number_format($total, 2, ',', '.') ?></td></tr>
        </table>
        <div style="margin-top:8px;font-size:10px;color:#888;">
          <?php
            $totalLetras = new NumberFormatter('es', NumberFormatter::SPELLOUT);
            echo 'Son: ' . ucfirst($totalLetras->format(floor($total))) . ' pesos';
          ?>
        </div>
        <div style="margin-top:4px;font-size:10px;color:#888;">
          Medios de pago: <?= $metodoLabels[$pedido['metodo_pago']] ?? $pedido['metodo_pago'] ?>
          · <?= $envioLabels[$pedido['tipo_envio'] ?? 'domicilio'] ?? 'Envío' ?>
        </div>
      </div>
    </div>

    <div class="footer">
      <div class="footer-left">
        <strong>ShopRive S.A.</strong> · CUIT 30-71234567-8<br>
        Av. Corrientes 1234, Buenos Aires, Argentina<br>
        Tel: +54 11 5555-1234 · soporte@shoprive.com<br>
        <span style="font-size:9px;color:#aaa;">Factura electrónica generada el <?= $pedido['fecha_factura'] ?> · CAE <?= $pedido['cae'] ?></span>
      </div>
      <div class="footer-right">
        <div class="qr-placeholder">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=Factura%20<?= urlencode($pedido['numero_factura']) ?>%20CAE%20<?= urlencode($pedido['cae']) ?>%20ShopRive" alt="QR AFIP">
        </div>
      </div>
    </div>

    <div class="actions no-print">
      <button class="btn-print" onclick="window.print()">Imprimir / PDF</button>
      <button class="btn-back" onclick="window.location.href='pedidos.php'">← Volver a Pedidos</button>
    </div>
  </div>
</body>
</html>
