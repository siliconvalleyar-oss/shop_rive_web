<?php
/**
 * Chat API - Chatbot with pattern matching
 *
 * Routes (via api/index.php):
 *   POST /api/chat
 *
 * Legacy: chat.php (no action needed)
 */

require_once __DIR__ . '/../lib/bootstrap.php';

// Handle legacy direct call
$action = $_GET['action'] ?? '';

/**
 * POST /api/chat
 */
function handleChat() {
  $data = getJsonBody();

  $v = new Validator($data, ['mensaje' => 'Mensaje']);
  $v->required('mensaje')->maxLength('mensaje', 500);
  if (!$v->passes()) Response::error($v->firstError(), 422);

  $mensaje = trim($data['mensaje']);

  global $pdo;

  // Try to load knowledge base from DB
  $conocimiento = [];
  try {
    $stmt = $pdo->prepare("SELECT * FROM chatbot_conocimiento ORDER BY id ASC");
    $stmt->execute();
    $conocimiento = $stmt->fetchAll();
  } catch (Exception $e) {
    Logger::debug('Chatbot DB not available, using fallback');
  }

  // Fallback knowledge base
  if (empty($conocimiento)) {
    $conocimiento = [
      ['patron' => 'hola|buenas|buen día|buen día|qué tal|que tal|hey|saludos', 'respuesta' => '¡Hola! Bienvenido a ShopRive 😊 ¿En qué puedo ayudarte hoy?'],
      ['patron' => 'precio|cuánto|cuanto|valor|costo|sale', 'respuesta' => 'Los precios varían según el producto. Todos los precios están visibles en la tienda. ¡Tenemos opciones para todos los bolsillos! 💰'],
      ['patron' => 'horario|atienden|abren|cierran|abierto', 'respuesta' => 'Atención al cliente: Lun a Vie de 9 a 18hs. Tienda online disponible 24/7. 🕘'],
      ['patron' => 'envió|envío|envían|envían|domicilio|entrega|llega|demora', 'respuesta' => 'Hacemos envíos a domicilio. El tiempo de entrega depende de tu ubicación (generalmente 3-7 días hábiles). También ofrecemos retiro en local. 🚚'],
      ['patron' => 'pago|pagar|tarjeta|transferencia|efectivo|mercado pago|cuota|cuotas', 'respuesta' => 'Aceptamos tarjeta de crédito/débito, transferencia bancaria, Mercado Pago, QR con Cuenta DNI, y efectivo. En la pantalla de pago podés elegir la opción que prefieras. 💳'],
      ['patron' => 'garantía|garantia|cambio|cambiar|devolver|falla|roto|problema', 'respuesta' => 'Todos los productos tienen garantía. Los electrónicos tienen 6 meses de garantía. Podés solicitar cambio de talle sin costo en productos de moda. 🛡️'],
      ['patron' => 'gracias|gracias|muchas gracias|agradezco|genial|excelente', 'respuesta' => '¡Gracias a vos! Si tenés alguna otra consulta, no dudes en preguntar. Que tengas un excelente día 😊'],
      ['patron' => 'catálogo|catálogo|productos|qué venden|qué venden|venden', 'respuesta' => 'Tenemos una gran variedad de productos: Electrónica (auriculares, relojes, tablets, parlantes), Moda (zapatillas, carteras, camperas, billeteras), Hogar (lámparas), Deportes (pesas). ¡Mirá nuestro catálogo en la tienda! 🛍️'],
      ['patron' => 'contacto|teléfono|teléfono|whatsapp|email|mail|ubicación|ubicacion|dirección|direccion', 'respuesta' => 'Podés contactarnos al +54 11 5555-1234, por WhatsApp al mismo número, o por email a soporte@shoprive.com. Estamos en Av. Corrientes 1234, Buenos Aires. 📍'],
      ['patron' => 'default', 'respuesta' => 'No estoy seguro de entender tu consulta. Podés llamarnos al +54 11 5555-1234 o escribirnos a soporte@shoprive.com para ayudarte mejor. 😊']
    ];
  }

  // Match against patterns
  $respuesta = '';
  $intencion = 'default';
  foreach ($conocimiento as $entry) {
    $patrones = explode('|', $entry['patron']);
    foreach ($patrones as $patron) {
      $patron = trim($patron);
      if ($patron === 'default') continue;
      if (preg_match('/\b' . preg_quote($patron, '/') . '\b/i', $mensaje)) {
        $respuesta = $entry['respuesta'];
        $intencion = $patron;
        break 2;
      }
    }
  }

  if (!$respuesta) {
    // Find default response
    foreach ($conocimiento as $entry) {
      if ($entry['patron'] === 'default') {
        $respuesta = $entry['respuesta'];
        break;
      }
    }
  }

  // Save chat log
  try {
    $stmt = $pdo->prepare("INSERT INTO chatbot_logs (mensaje, respuesta, intencion) VALUES (?, ?, ?)");
    $stmt->execute([$mensaje, $respuesta, $intencion]);
  } catch (Exception $e) {
    Logger::debug('Failed to save chat log: ' . $e->getMessage());
  }

  Response::success([
    'respuesta' => $respuesta,
    'intencion' => $intencion
  ]);
}
