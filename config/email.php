<?php
/**
 * Email templates for ShopRive
 * Generates HTML emails for different order stages and product types.
 */

function emailHeader($title) {
    return '
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
    <body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:30px 20px;">
    <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
    <tr><td style="background:linear-gradient(135deg,#6c5ce7,#a29bfe);padding:32px 40px;text-align:center;">
      <h1 style="color:#fff;margin:0;font-size:24px;letter-spacing:-0.5px;">ShopRive</h1>
      <p style="color:rgba(255,255,255,0.85);margin:4px 0 0;font-size:13px;">' . htmlspecialchars($title) . '</p>
    </td></tr>
    <tr><td style="padding:32px 40px;">';
}

function emailFooter() {
    return '
    </td></tr>
    <tr><td style="background:#f8f8ff;padding:20px 40px;text-align:center;border-top:1px solid #eee;">
      <p style="color:#888;font-size:12px;margin:0;">ShopRive S.A. · Av. Corrientes 1234, Buenos Aires, Argentina<br>
      <a href="mailto:soporte@shoprive.com" style="color:#6c5ce7;text-decoration:none;">soporte@shoprive.com</a> · +54 11 5555-1234</p>
    </td></tr>
    </table>
    </td></tr></table>
    </body></html>';
}

function itemsTable($items) {
    $html = '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:16px 0;">
    <tr><td style="padding:10px 12px;background:#6c5ce7;color:#fff;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;border-radius:8px 0 0 0;">Producto</td>
    <td style="padding:10px 12px;background:#6c5ce7;color:#fff;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;text-align:center;">Cant.</td>
    <td style="padding:10px 12px;background:#6c5ce7;color:#fff;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;text-align:right;border-radius:0 8px 0 0;">Precio</td>
    <td style="padding:10px 12px;background:#6c5ce7;color:#fff;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;text-align:right;">Subtotal</td></tr>';
    $total = 0;
    foreach ($items as $item) {
        $subtotal = floatval($item['precio']) * intval($item['cantidad']);
        $total += $subtotal;
        $html .= '<tr><td style="padding:10px 12px;border-bottom:1px solid #eee;font-size:13px;">' . htmlspecialchars($item['nombre']) . '</td>
        <td style="padding:10px 12px;border-bottom:1px solid #eee;font-size:13px;text-align:center;">' . (int)$item['cantidad'] . '</td>
        <td style="padding:10px 12px;border-bottom:1px solid #eee;font-size:13px;text-align:right;">$' . number_format(floatval($item['precio']), 0, ',', '.') . '</td>
        <td style="padding:10px 12px;border-bottom:1px solid #eee;font-size:13px;text-align:right;">$' . number_format($subtotal, 0, ',', '.') . '</td></tr>';
    }
    $html .= '<tr><td colspan="3" style="padding:12px;font-size:15px;font-weight:700;text-align:right;border-top:2px solid #6c5ce7;">Total:</td>
    <td style="padding:12px;font-size:15px;font-weight:700;text-align:right;border-top:2px solid #6c5ce7;color:#6c5ce7;">$' . number_format($total, 0, ',', '.') . '</td></tr>';
    $html .= '</table>';
    return $html;
}

function sendEmail($to, $subject, $body) {
    // Use Mailer if available, fallback to mail()
    if (class_exists('Mailer')) {
        Mailer::quickSend($to, $subject, $body);
        return;
    }
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: ShopRive <noreply@shoprive.com>\r\n";
    @mail($to, "=?UTF-8?B?" . base64_encode($subject) . "?=", $body, $headers);
}

/**
 * Email: Pedido recibido (order created, pending payment)
 */
function emailPedidoRecibido($pedido, $items) {
    $title = "Pedido #{$pedido['id']} Recibido";
    $body = emailHeader($title);
    $body .= '<h2 style="color:#333;font-size:20px;margin:0 0 8px;">¡Hola ' . htmlspecialchars($pedido['nombre']) . '!</h2>';
    $body .= '<p style="color:#666;font-size:14px;line-height:1.6;">Recibimos tu pedido <strong>#' . $pedido['id'] . '</strong> y está pendiente de pago.</p>';

    // Category-based message
    $cats = [];
    foreach ($items as $item) { $cats[] = $item['nombre']; }
    $body .= '<p style="color:#666;font-size:14px;line-height:1.6;">';
    if (preg_match('/auricular|reloj|tablet|parlante|electrónica|electronica/i', implode(' ', $cats))) {
        $body .= '📱 <strong>Productos electrónicos:</strong> Recordá que todos nuestros dispositivos tienen garantía de 6 meses.';
    } elseif (preg_match('/zapatilla|campera|bolso|billetera|moda|mochila/i', implode(' ', $cats))) {
        $body .= '👗 <strong>Productos de moda:</strong> Disponemos de todos los talles. Podés solicitar cambio de talle sin costo.';
    } elseif (preg_match('/lámpara|lámpara|hogar/i', implode(' ', $cats))) {
        $body .= '🏠 <strong>Productos para el hogar:</strong> Incluyen manual de instalación y garantía.';
    } elseif (preg_match('/pesas|deportes|deporte|ejercicio/i', implode(' ', $cats))) {
        $body .= '🏋️ <strong>Artículos deportivos:</strong> Equipamiento de alta calidad con resistencia garantizada.';
    } else {
        $body .= 'Gracias por confiar en ShopRive. Vamos a procesar tu pedido pronto.';
    }
    $body .= '</p>';

    $body .= itemsTable($items);
    $body .= '<p style="color:#666;font-size:13px;"><strong>Forma de envío:</strong> ' . ($pedido['tipo_envio'] === 'retiro' ? 'Retiro en local (Av. Corrientes 1234)' : 'Envío a domicilio') . '</p>';
    $body .= '<p style="color:#666;font-size:13px;"><strong>Método de pago:</strong> ' . htmlspecialchars($pedido['metodo_pago']) . '</p>';
    $body .= '<p style="color:#888;font-size:13px;">Una vez que confirmes el pago, te notificaremos y comenzaremos a preparar tu pedido.</p>';
    $body .= emailFooter();
    return $body;
}

/**
 * Email: Pago confirmado (payment successful)
 */
function emailPagoConfirmado($pedido, $items, $cardLast4 = '') {
    $title = "Pago Confirmado - Pedido #{$pedido['id']}";
    $body = emailHeader($title);
    $body .= '<h2 style="color:#333;font-size:20px;margin:0 0 8px;">¡Pago recibido, ' . htmlspecialchars($pedido['nombre']) . '! ✅</h2>';
    $body .= '<p style="color:#666;font-size:14px;line-height:1.6;">El pago del pedido <strong>#' . $pedido['id'] . '</strong> fue <strong style="color:#00b894;">confirmado exitosamente</strong>.</p>';
    if ($cardLast4) {
        $body .= '<p style="color:#666;font-size:13px;">Tarjeta terminada en <strong>**** ' . $cardLast4 . '</strong></p>';
    }
    $body .= itemsTable($items);
    $body .= '<p style="color:#666;font-size:13px;"><strong>Forma de envío:</strong> ' . ($pedido['tipo_envio'] === 'retiro' ? 'Retiro en local (Av. Corrientes 1234)' : 'Envío a domicilio') . '</p>';
    if (!empty($pedido['numero_factura'])) {
        $body .= '<p style="color:#666;font-size:13px;"><strong>Factura electrónica:</strong> Nº ' . $pedido['numero_factura'] . ' — CAE: ' . $pedido['cae'] . '</p>';
    }
    $body .= '<p style="color:#888;font-size:13px;">Estamos preparando tu pedido para envío. Te avisaremos cuando esté en camino. 🚚</p>';
    $body .= emailFooter();
    return $body;
}

/**
 * Email: Factura generada (invoice)
 */
function emailFactura($pedido, $items) {
    $title = "Factura Electrónica - Pedido #{$pedido['id']}";
    $body = emailHeader($title);
    $body .= '<h2 style="color:#333;font-size:20px;margin:0 0 8px;">Factura Electrónica 📄</h2>';
    $body .= '<p style="color:#666;font-size:14px;line-height:1.6;">Generamos la factura electrónica para tu pedido <strong>#' . $pedido['id'] . '</strong>.</p>';
    $body .= '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f8ff;border-radius:12px;padding:16px;margin:16px 0;">
    <tr><td style="font-size:13px;color:#888;padding:4px 0;">N° Factura</td><td style="font-size:14px;font-weight:600;padding:4px 0;text-align:right;">' . htmlspecialchars($pedido['numero_factura']) . '</td></tr>
    <tr><td style="font-size:13px;color:#888;padding:4px 0;">CAE</td><td style="font-size:14px;font-weight:600;padding:4px 0;text-align:right;">' . htmlspecialchars($pedido['cae']) . '</td></tr>
    <tr><td style="font-size:13px;color:#888;padding:4px 0;">Vto. CAE</td><td style="font-size:14px;font-weight:600;padding:4px 0;text-align:right;">' . htmlspecialchars($pedido['cae_vencimiento']) . '</td></tr>
    <tr><td style="font-size:13px;color:#888;padding:4px 0;">Emisor</td><td style="font-size:14px;font-weight:600;padding:4px 0;text-align:right;">ShopRive S.A. · CUIT 30-71234567-8</td></tr>
    </table>';
    $body .= itemsTable($items);
    $body .= '<p style="color:#888;font-size:13px;">Podés descargar el PDF de tu factura desde el panel de administración o solicitándola a soporte@shoprive.com</p>';
    $body .= emailFooter();
    return $body;
}

/**
 * Email: Presupuesto / Cotización (quote/budget)
 */
function emailPresupuesto($cliente, $items, $presupuestoId, $validez = '7 días') {
    $total = 0;
    foreach ($items as $item) { $total += floatval($item['precio']) * intval($item['cantidad']); }

    $title = "Presupuesto #$presupuestoId - ShopRive";
    $body = emailHeader($title);
    $body .= '<h2 style="color:#333;font-size:20px;margin:0 0 8px;">Presupuesto personalizado 🎯</h2>';
    $body .= '<p style="color:#666;font-size:14px;line-height:1.6;">Hola ' . htmlspecialchars($cliente['nombre'] ?? '') . ', gracias por interesarte en nuestros productos.</p>';
    $body .= '<p style="color:#666;font-size:13px;line-height:1.6;">A continuación detallamos el presupuesto solicitado. Este presupuesto tiene una validez de <strong>' . $validez . '</strong>.</p>';

    $html = '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:16px 0;">
    <tr><td style="padding:10px 12px;background:#6c5ce7;color:#fff;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;border-radius:8px 0 0 0;">Producto</td>
    <td style="padding:10px 12px;background:#6c5ce7;color:#fff;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;text-align:center;border-radius:0 8px 0 0;">Cant.</td>
    <td style="padding:10px 12px;background:#6c5ce7;color:#fff;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;text-align:right;">Precio Unit.</td>
    <td style="padding:10px 12px;background:#6c5ce7;color:#fff;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;text-align:right;">Subtotal</td></tr>';
    foreach ($items as $item) {
        $subtotal = floatval($item['precio']) * intval($item['cantidad']);
        $html .= '<tr><td style="padding:10px 12px;border-bottom:1px solid #eee;font-size:13px;">' . htmlspecialchars($item['nombre']) . '</td>
        <td style="padding:10px 12px;border-bottom:1px solid #eee;font-size:13px;text-align:center;">' . (int)$item['cantidad'] . '</td>
        <td style="padding:10px 12px;border-bottom:1px solid #eee;font-size:13px;text-align:right;">$' . number_format(floatval($item['precio']), 0, ',', '.') . '</td>
        <td style="padding:10px 12px;border-bottom:1px solid #eee;font-size:13px;text-align:right;">$' . number_format($subtotal, 0, ',', '.') . '</td></tr>';
    }
    $html .= '<tr><td colspan="3" style="padding:12px;font-size:15px;font-weight:700;text-align:right;border-top:2px solid #6c5ce7;">Total Presupuesto:</td>
    <td style="padding:12px;font-size:15px;font-weight:700;text-align:right;border-top:2px solid #6c5ce7;color:#6c5ce7;">$' . number_format($total, 0, ',', '.') . '</td></tr>';
    $html .= '</table>';

    $body .= $html;

    // Category-based recommendations
    $catList = [];
    foreach ($items as $item) { $catList[] = $item['categoria'] ?? ''; }
    $uniqueCats = array_unique(array_filter($catList));
    if (!empty($uniqueCats)) {
        $body .= '<div style="background:#f8f8ff;border-radius:12px;padding:16px;margin:16px 0;">';
        $body .= '<p style="color:#333;font-size:14px;font-weight:600;margin:0 0 8px;">💡 Recomendaciones</p>';
        foreach ($uniqueCats as $cat) {
            if (preg_match('/electronica/i', $cat)) {
                $body .= '<p style="color:#666;font-size:13px;margin:4px 0;">• Ofrecemos <strong>12 cuotas sin interés</strong> en todos los productos de electrónica.</p>';
            } elseif (preg_match('/moda/i', $cat)) {
                $body .= '<p style="color:#666;font-size:13px;margin:4px 0;">• Disponemos de <strong>todos los talles y colores</strong>. Consultá por muestras sin costo.</p>';
            } elseif (preg_match('/hogar/i', $cat)) {
                $body .= '<p style="color:#666;font-size:13px;margin:4px 0;">• Incluye <strong>instalación gratuita</strong> en CABA y GBA.</p>';
            } elseif (preg_match('/deportes/i', $cat)) {
                $body .= '<p style="color:#666;font-size:13px;margin:4px 0;">• <strong>Envío sin cargo</strong> en compras mayores a $50.000.</p>';
            }
        }
        $body .= '</div>';
    }

    $body .= '<p style="color:#666;font-size:13px;">📍 <strong>Retiro en local:</strong> Av. Corrientes 1234, Buenos Aires</p>';
    $body .= '<p style="color:#666;font-size:13px;">📞 Consultas al <strong>+54 11 5555-1234</strong> o respondiendo este mail.</p>';
    $body .= emailFooter();
    return $body;
}

/**
 * Send email based on type
 */
function enviarEmailTipo($tipo, $to, $pedido, $items, $extra = []) {
    // 1. Intentar obtener la plantilla formateada desde n8n
    $n8nUrl = 'http://localhost:5679/webhook/shoprive-emails';
    $postData = json_encode([
        'tipo' => $tipo,
        'to' => $to,
        'pedido' => $pedido,
        'items' => $items,
        'extra' => $extra
    ]);

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $postData,
            'timeout' => 2 // 2 segundos máximo para no colgar la web si n8n no responde
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($n8nUrl, false, $context);

    if ($response !== false) {
        $resData = json_decode($response, true);
        if (isset($resData['subject']) && isset($resData['body'])) {
            sendEmail($to, $resData['subject'], $resData['body']);
            return;
        }
    }

    // 2. Fallback: Si n8n falla o está apagado, usar plantillas PHP locales
    $subject = '';
    $body = '';

    switch ($tipo) {
        case 'recepcion':
            $subject = "Pedido #{$pedido['id']} recibido - ShopRive";
            $body = emailPedidoRecibido($pedido, $items);
            break;
        case 'pago_confirmado':
            $subject = "Pago confirmado - Pedido #{$pedido['id']} - ShopRive ✅";
            $body = emailPagoConfirmado($pedido, $items, $extra['cardLast4'] ?? '');
            break;
        case 'factura':
            $subject = "Factura electrónica - Pedido #{$pedido['id']} - ShopRive";
            $body = emailFactura($pedido, $items);
            break;
        case 'presupuesto':
            $subject = "Presupuesto #{$extra['presupuesto_id']} - ShopRive";
            $body = emailPresupuesto($extra['cliente'] ?? ['nombre' => ''], $items, $extra['presupuesto_id'] ?? '0001');
            break;
    }
    if ($body) {
        sendEmail($to, $subject, $body);
    }
}
