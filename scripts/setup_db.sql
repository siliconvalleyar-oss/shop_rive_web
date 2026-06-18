-- Base de datos ShopRive
CREATE DATABASE IF NOT EXISTS shoprive;
USE shoprive;

-- =============================================
-- USUARIOS
-- =============================================
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rol ENUM('usuario','admin') DEFAULT 'usuario',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin por defecto (admin@shoprive.com / admin123)
INSERT INTO usuarios (nombre, email, password, rol) VALUES
  ('Admin', 'admin@shoprive.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
  ('Usuario Demo', 'user@shoprive.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario')
ON DUPLICATE KEY UPDATE nombre=nombre;

-- =============================================
-- PRODUCTOS (desde JS)
-- =============================================
CREATE TABLE IF NOT EXISTS productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(200) NOT NULL,
  categoria VARCHAR(50) NOT NULL,
  precio DECIMAL(10,2) NOT NULL,
  riv_file VARCHAR(100) DEFAULT 'car',
  color VARCHAR(7) DEFAULT '#6c5ce7',
  stock INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO productos (nombre, categoria, precio, riv_file, color, stock) VALUES
  ('Auriculares Pro', 'electronica', 45000, 'hero-ui-animation', '#6c5ce7', 25),
  ('Reloj Inteligente', 'electronica', 65000, 'rotating-can', '#fd79a8', 15),
  ('Zapatillas Urbanas', 'moda', 52000, 'shoe-showcase', '#00b894', 30),
  ('Bolso de Mano', 'moda', 38000, 'purse-360', '#fdcb6e', 20),
  ('Lámpara LED', 'hogar', 18000, 'off_road_car_0_6', '#e17055', 50),
  ('Campera Premium', 'moda', 78000, 'shoe-showcase', '#00cec9', 12),
  ('Tablet 10"', 'electronica', 120000, 'rotating-can', '#a29bfe', 8),
  ('Set de Pesas', 'deportes', 35000, 'off_road_car_0_6', '#fab1a0', 18),
  ('Billetera Elegante', 'moda', 22000, 'purse-360', '#6c5ce7', 35),
  ('Parlante Portátil', 'electronica', 32000, 'hero-ui-animation', '#fd79a8', 22)
ON DUPLICATE KEY UPDATE nombre=nombre;

-- =============================================
-- CARRITO (persistente por usuario)
-- =============================================
CREATE TABLE IF NOT EXISTS carrito (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT DEFAULT 1,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  UNIQUE KEY uk_carrito (usuario_id, producto_id)
);

-- =============================================
-- CHATBOT - Base de conocimiento
-- =============================================
CREATE TABLE IF NOT EXISTS chatbot_conocimiento (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patron VARCHAR(500) NOT NULL,
  respuesta TEXT NOT NULL,
  activo TINYINT(1) DEFAULT 1
);

INSERT INTO chatbot_conocimiento (patron, respuesta) VALUES
  ('hola|buenas|buenos dias|buenas tardes|hey|que tal', '¡Hola! Bienvenido a ShopRive. ¿En qué puedo ayudarte? Puedo informarte sobre productos, precios, envíos y más.'),
  ('productos|qué venden|catalogo|tienda', 'En ShopRive vendemos tecnología, moda, hogar y deportes. Tenemos auriculares, relojes, mochilas, zapatillas, lámparas, camperas, tablets y pesas. ¿Qué categoría te interesa?'),
  ('electronica|tecnología|auriculares|reloj|tablet', 'En electrónica tenemos:\n- Auriculares Pro: $45.000\n- Reloj Inteligente: $65.000\n- Tablet 10": $120.000\n¿Querés más detalles de alguno?'),
  ('moda|ropa|mochila|campera', 'En moda tenemos:\n- Mochila Urbana: $28.000\n- Campera Premium: $78.000\n¿Te gustaría ver más opciones?'),
  ('hogar|casa|lámpara|decoracion', 'En hogar tenemos Lámpara LED a $18.000. Ideal para iluminar cualquier ambiente. ¿Te interesa?'),
  ('deportes|pesas|ejercicio|gym', 'En deportes tenemos Set de Pesas por $35.000 y Zapatillas Sport por $52.000. ¿Algo más que busques?'),
  ('precio|cuanto cuesta|cuanto vale|costo', 'Los precios varían según el producto. Tenemos desde $18.000 hasta $120.000. ¿Qué producto te interesa? Te paso el precio exacto.'),
  ('envio|envíos|entrega|demora|cuanto tarda', 'Hacemos envíos a todo el país. El tiempo estimado es de 3 a 7 días hábiles. En compras mayores a $50.000 el envío es GRATIS.'),
  ('pago|formas de pago|tarjeta|transferencia|efectivo', 'Aceptamos tarjetas de crédito/débito, transferencia bancaria y efectivo. Podés pagar en hasta 12 cuotas sin interés.'),
  ('horario|atención|horarios|local', 'Nuestra atención al cliente es de lunes a viernes de 9:00 a 18:00 hs. Los pedidos online se procesan las 24 hs.'),
  ('devolucion|cambio|reembolso|garantia', 'Tenés 30 días para cambios o devoluciones. Todos nuestros productos tienen garantía por 6 meses.'),
  ('gracias|gracias|muchas gracias|thanks', '¡De nada! Si necesitás algo más, estoy aquí para ayudarte. Que tengas un excelente día 🚀'),
  ('admin|administrador|panel', 'Si sos administrador, podés acceder al panel en /admin/. Necesitás una cuenta con permisos de administrador.'),
  ('chatbot|quien eres|que eres|bot', 'Soy el asistente virtual de ShopRive 🤖 Estoy aquí para ayudarte con productos, precios, envíos y todo lo que necesites.'),
  ('default', 'Disculpa, no entendí bien tu consulta. Podés preguntarme sobre:\n- Productos y precios\n- Envíos\n- Formas de pago\n- Devoluciones\n- Horarios de atención\nO escribí "hola" para empezar.')
ON DUPLICATE KEY UPDATE respuesta=respuesta;

-- =============================================
-- CHATBOT - Logs de conversaciones
-- =============================================
CREATE TABLE IF NOT EXISTS chatbot_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mensaje TEXT NOT NULL,
  respuesta TEXT NOT NULL,
  intencion VARCHAR(100) DEFAULT 'default',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
