<?php
require_once __DIR__ . '/../config/database.php';

if (!$pdo) {
    echo "Error al inicializar la base de datos.\n";
    exit(1);
}

// --- USUARIOS ---
$pdo->exec("CREATE TABLE usuarios");
$hash = password_hash('123456', PASSWORD_DEFAULT);
$pdo->prepare("INSERT INTO usuarios (id, nombre, email, password, rol) VALUES (?, ?, ?, ?, ?)")->execute([1, 'Admin', 'admin@shoprive.com', $hash, 'admin']);
$pdo->prepare("INSERT INTO usuarios (id, nombre, email, password, rol) VALUES (?, ?, ?, ?, ?)")->execute([2, 'Usuario Demo', 'user@shoprive.com', $hash, 'usuario']);

// --- PRODUCTOS ---
$pdo->exec("CREATE TABLE productos");
$productos = [
    [1, 'Auriculares Pro', 'electronica', 45000, 'hero-ui-animation', '#6c5ce7', 25, 0],
    [2, 'Reloj Inteligente', 'electronica', 65000, 'rotating-can', '#fd79a8', 15, 0],
    [3, 'Zapatillas Urbanas', 'moda', 52000, 'shoe-showcase', '#00b894', 30, 0],
    [4, 'Bolso de Mano', 'moda', 38000, 'purse-360', '#fdcb6e', 20, 0],
    [5, 'Lámpara LED', 'hogar', 18000, 'off_road_car_0_6', '#e17055', 50, 0],
    [6, 'Campera Premium', 'moda', 78000, 'shoe-showcase', '#00cec9', 12, 0],
    [7, 'Tablet 10"', 'electronica', 120000, 'rotating-can', '#a29bfe', 8, 0],
    [8, 'Set de Pesas', 'deportes', 35000, 'off_road_car_0_6', '#fab1a0', 18, 0],
    [9, 'Billetera Elegante', 'moda', 22000, 'purse-360', '#6c5ce7', 35, 0],
    [10, 'Parlante Portátil', 'electronica', 32000, 'hero-ui-animation', '#fd79a8', 22, 0],
];
$stmt = $pdo->prepare("INSERT INTO productos (id, nombre, categoria, precio, riv_file, color, stock, solo_retiro) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($productos as $p) $stmt->execute($p);

// --- CARRITO ---
$pdo->exec("CREATE TABLE carrito");

// --- CHATBOT CONOCIMIENTO ---
$pdo->exec("CREATE TABLE chatbot_conocimiento");
$conocimiento = [
    [1, 'hola|buenas|buenos dias|buenas tardes|que tal', '¡Hola! Bienvenido a ShopRive. ¿En qué puedo ayudarte? Puedo informarte sobre productos, precios, envíos y más.'],
    [2, 'productos|qué venden|catalogo|tienda', 'En ShopRive vendemos tecnología, moda, hogar y deportes. Tenemos auriculares, relojes, mochilas, zapatillas, lámparas, camperas, tablets y pesas. ¿Qué categoría te interesa?'],
    [3, 'electronica|tecnología|auriculares|reloj|tablet', 'En electrónica tenemos:\n- Auriculares Pro: $45.000\n- Reloj Inteligente: $65.000\n- Tablet 10": $120.000\n¿Querés más detalles de alguno?'],
    [4, 'moda|ropa|mochila|campera', 'En moda tenemos:\n- Mochila Urbana: $28.000\n- Campera Premium: $78.000\n¿Te gustaría ver más opciones?'],
    [5, 'hogar|casa|lámpara|decoracion', 'En hogar tenemos Lámpara LED a $18.000. Ideal para iluminar cualquier ambiente. ¿Te interesa?'],
    [6, 'deportes|pesas|ejercicio|gym', 'En deportes tenemos Set de Pesas por $35.000 y Zapatillas Sport por $52.000. ¿Algo más que busques?'],
    [7, 'precio|cuanto cuesta|cuanto vale|costo', 'Los precios varían según el producto. Tenemos desde $18.000 hasta $120.000. ¿Qué producto te interesa? Te paso el precio exacto.'],
    [8, 'envio|envíos|entrega|demora|cuanto tarda', 'Hacemos envíos a todo el país. El tiempo estimado es de 3 a 7 días hábiles. En compras mayores a $50.000 el envío es GRATIS.'],
    [9, 'pago|formas de pago|tarjeta|transferencia|efectivo', 'Aceptamos tarjetas de crédito/débito, transferencia bancaria y efectivo. Podés pagar en hasta 12 cuotas sin interés.'],
    [10, 'horario|atención|horarios|local', 'Nuestra atención al cliente es de lunes a viernes de 9:00 a 18:00 hs. Los pedidos online se procesan las 24 hs.'],
    [11, 'devolucion|cambio|reembolso|garantia', 'Tenés 30 días para cambios o devoluciones. Todos nuestros productos tienen garantía por 6 meses.'],
    [12, 'gracias|muchas gracias|thanks', '¡De nada! Si necesitás algo más, estoy aquí para ayudarte. Que tengas un excelente día 🚀'],
    [13, 'admin|administrador|panel', 'Si sos administrador, podés acceder al panel en /admin/. Necesitás una cuenta con permisos de administrador.'],
    [14, 'chatbot|quien eres|que eres|bot', 'Soy el asistente virtual de ShopRive 🤖 Estoy aquí para ayudarte con productos, precios, envíos y todo lo que necesites.'],
    [15, 'default', 'Disculpa, no entendí bien tu consulta. Podés preguntarme sobre:\n- Productos y precios\n- Envíos\n- Formas de pago\n- Devoluciones\n- Horarios de atención\nO escribí "hola" para empezar.'],
];
$stmt = $pdo->prepare("INSERT INTO chatbot_conocimiento (id, patron, respuesta) VALUES (?, ?, ?)");
foreach ($conocimiento as $c) $stmt->execute($c);

// --- CHATBOT LOGS ---
$pdo->exec("CREATE TABLE chatbot_logs");

// --- PEDIDOS ---
$pdo->exec("CREATE TABLE pedidos");

// --- DETALLE PEDIDO ---
$pdo->exec("CREATE TABLE detalle_pedido");

echo "Base de datos inicializada con éxito.\n";
echo "Usuarios:\n";
echo "  admin@shoprive.com / 123456 (Admin)\n";
echo "  user@shoprive.com / 123456 (Demo)\n";
