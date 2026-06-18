<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$mensaje = trim(mb_strtolower($data['mensaje'] ?? ''));

if (!$mensaje) {
    echo json_encode(['success' => false, 'error' => 'Mensaje vacío']);
    exit;
}

// Intentar cargar conocimiento desde BD, si no usar fallback
$conocimiento = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT patron, respuesta FROM chatbot_conocimiento WHERE activo = 1 ORDER BY id");
        $conocimiento = $stmt->fetchAll();
    } catch (Exception $e) {}
}

if (empty($conocimiento)) {
    $conocimiento = [
        ['patron' => 'hola|buenas|buenos días|buenas tardes|buenas noches|hey', 'respuesta' => '¡Hola! Soy el asistente de ShopRive. ¿En qué puedo ayudarte?'],
        ['patron' => 'precio|cuánto cuesta|valor|costar', 'respuesta' => 'Los precios están visibles en cada producto. Si necesitas más detalles, pregúntame por un producto específico.'],
        ['patron' => 'horario|atención|abierto|abren|cierran', 'respuesta' => 'Atenemos de lunes a viernes de 9 a 18 h. Los fines de semana respondemos consultas por este chat.'],
        ['patron' => 'envío|envios|envían|entrega|envío gratis', 'respuesta' => 'Hacemos envíos a todo el país. El costo varía según tu ubicación. Los pedidos superiores a $50.000 tienen envío gratis.'],
        ['patron' => 'pago|tarjeta|transferencia|efectivo|método de pago|medios de pago', 'respuesta' => 'Aceptamos tarjetas de crédito/débito, transferencia bancaria y Mercado Pago. También podés pagar en efectivo con depósito bancario.'],
        ['patron' => 'garantía|garantia|cambio|devolver|reembolso', 'respuesta' => 'Todos nuestros productos tienen 30 días de garantía. Podés cambiar o devolver sin costo dentro de ese período.'],
        ['patron' => 'gracias|muchas gracias|gracias totales', 'respuesta' => '¡Gracias a vos por elegir ShopRive! Si necesitas algo más, acá estoy. 😊'],
        ['patron' => 'catálogo|productos|qué venden|tienda|comprar', 'respuesta' => 'Tenemos auriculares, relojes inteligentes, mochilas, zapatillas, lámparas, camperas, tablets y sets de pesas. Navegá por las categorías para verlos todos.'],
        ['patron' => 'contacto|teléfono|tel|whatsapp|mail|email', 'respuesta' => 'Podes contactarnos al WhatsApp: +54 11 5555-1234 o al mail: soporte@shoprive.com'],
        ['patron' => 'default', 'respuesta' => 'Disculpa, no entendí la consulta. Podés preguntarme por productos, precios, envíos, pagos o garantía.'],
    ];
}

$respuesta = null;
$intencion = 'default';

foreach ($conocimiento as $item) {
    $patrones = explode('|', $item['patron']);
    foreach ($patrones as $patron) {
        $patron = trim($patron);
        if ($patron === 'default') continue;
        if (preg_match('/\b' . preg_quote($patron, '/') . '\b/i', $mensaje)) {
            $respuesta = $item['respuesta'];
            $intencion = $patron;
            break 2;
        }
    }
}

// Fallback
if (!$respuesta) {
    foreach ($conocimiento as $item) {
        if (trim($item['patron']) === 'default') {
            $respuesta = $item['respuesta'];
            break;
        }
    }
}

// Guardar conversación (opcional)
if ($pdo) {
    try {
        $stmt = $pdo->prepare("INSERT INTO chatbot_logs (mensaje, respuesta, intencion) VALUES (?, ?, ?)");
        $stmt->execute([$mensaje, $respuesta, $intencion]);
    } catch (Exception $e) {
        // Tabla no existe o error - ignorar
    }
}

echo json_encode([
    'success' => true,
    'respuesta' => nl2br(htmlspecialchars($respuesta)),
    'intencion' => $intencion
]);
