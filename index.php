<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ShopRive - Tienda Online</title>
  <link rel="stylesheet" href="css/style.css">
  <?php require_once __DIR__ . '/config/apariencia.php'; renderThemeStyles(); ?>
  <script src="https://unpkg.com/@rive-app/webgl@2.9.1"></script>
</head>
<body data-user="<?= $_SESSION['user_id'] ?? '' ?>" data-rol="<?= $_SESSION['user_rol'] ?? '' ?>">

  <!-- HEADER -->
  <header class="header">
    <div class="header-inner">
      <div class="logo">
        <canvas id="logo-rive" width="48" height="48" class="logo-canvas"></canvas>
        <span class="logo-text">Shop<span class="accent">Rive</span></span>
      </div>
      <nav class="nav">
        <a href="#inicio" class="nav-link active" onclick="showSection('inicio')">Inicio</a>
        <a href="#productos" class="nav-link" onclick="showSection('productos')">Productos</a>
        <a href="#ofertas" class="nav-link" onclick="showSection('ofertas')">Ofertas</a>
        <a href="#contacto" class="nav-link" onclick="showSection('contacto')">Contacto</a>
      </nav>
      <div class="header-actions">
        <?php if (isset($_SESSION['user_id'])): ?>
          <span class="user-greeting" style="color:var(--text-muted);font-size:0.85rem;">
            <?= htmlspecialchars($_SESSION['user_nombre']) ?>
            <?php if ($_SESSION['user_rol'] === 'admin'): ?>
              <a href="admin/index.php" style="color:var(--primary);text-decoration:none;">[Admin]</a>
            <?php endif; ?>
            <a href="auth/logout.php" style="color:var(--accent);text-decoration:none;margin-left:8px;">Salir</a>
          </span>
        <?php else: ?>
          <a href="auth/login.php" class="nav-link" style="font-size:0.85rem;">Ingresar</a>
          <a href="auth/register.php" class="nav-link" style="font-size:0.85rem;">Registro</a>
        <?php endif; ?>
        <button class="icon-btn cart-btn" aria-label="Carrito" onclick="toggleCart()">
          <svg viewBox="0 0 24 24" class="icon-svg cart-icon" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
          </svg>
          <span class="cart-badge" id="cart-badge">0</span>
        </button>
      </div>
    </div>
  </header>

  <!-- HERO CAROUSEL -->
  <section class="hero" id="inicio">
    <div class="hero-slides" id="hero-slides">
      <div class="hero-slide active" data-index="0">
        <div class="hero-content">
          <h1 class="hero-title">Descubre lo Último en Tecnología</h1>
          <p class="hero-subtitle">Productos innovadores con los mejores precios del mercado</p>
          <a href="#" class="btn-primary btn-cta">
            <canvas id="btn-cta-rive" width="28" height="28" style="width:28px;height:28px;vertical-align:middle;margin-right:8px;display:inline-block;"></canvas>
            Ver Ofertas
          </a>
        </div>
        <canvas id="hero-rive-0" class="hero-rive-canvas" width="700" height="500"></canvas>
      </div>
      <div class="hero-slide" data-index="1">
        <div class="hero-content">
          <h1 class="hero-title">Colección de Moda</h1>
          <p class="hero-subtitle">Descubrí las últimas tendencias en calzado y accesorios</p>
          <a href="#" class="btn-primary">Ver Colección</a>
        </div>
        <canvas id="hero-rive-1" class="hero-rive-canvas" width="700" height="500"></canvas>
      </div>
      <div class="hero-slide" data-index="2">
        <div class="hero-content">
          <h1 class="hero-title">Accesorios Elegantes</h1>
          <p class="hero-subtitle">Bolsos y billeteras con diseño único para cada estilo</p>
          <a href="#" class="btn-primary">Explorar</a>
        </div>
        <canvas id="hero-rive-2" class="hero-rive-canvas" width="700" height="500"></canvas>
      </div>
    </div>
    <div class="carousel-controls">
      <button class="carousel-arrow prev" onclick="prevSlide()">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
      </button>
      <div class="carousel-dots" id="carousel-dots"></div>
      <button class="carousel-arrow next" onclick="nextSlide()">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="9 18 15 12 9 6"/>
        </svg>
      </button>
    </div>
  </section>

  <!-- CATEGORIES -->
  <section class="categories">
    <h2 class="section-title">Categorías</h2>
    <div class="categories-grid">
      <div class="category-card" onclick="filterProducts('electronica')">
        <canvas id="cat-electronica" width="80" height="80" class="category-rive"></canvas>
        <span>Electrónica</span>
      </div>
      <div class="category-card" onclick="filterProducts('moda')">
        <canvas id="cat-moda" width="80" height="80" class="category-rive"></canvas>
        <span>Moda</span>
      </div>
      <div class="category-card" onclick="filterProducts('hogar')">
        <canvas id="cat-hogar" width="80" height="80" class="category-rive"></canvas>
        <span>Hogar</span>
      </div>
      <div class="category-card" onclick="filterProducts('deportes')">
        <canvas id="cat-deportes" width="80" height="80" class="category-rive"></canvas>
        <span>Deportes</span>
      </div>
    </div>
  </section>

  <!-- PRODUCTS GALLERY -->
  <section class="products" id="productos">
    <h2 class="section-title">Galería de Productos</h2>
    <p class="section-subtitle">Hacé clic para agregar al carrito directamente</p>
    <div class="products-grid" id="products-grid"></div>
  </section>

  <!-- OFERTAS -->
  <section class="ofertas" id="ofertas">
    <h2 class="section-title">Ofertas Especiales</h2>
    <p class="section-subtitle">Descuentos imperdibles en productos seleccionados</p>
    <div class="ofertas-grid" id="ofertas-grid"></div>
  </section>

  <!-- CONTACTO -->
  <section class="contacto" id="contacto">
    <h2 class="section-title">Contacto</h2>
    <div class="contacto-grid">
      <div class="contacto-card">
        <div class="contacto-info-list">
          <div class="contacto-item">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <span>soporte@shoprive.com</span>
            <button class="copy-btn" onclick="copyEmail(this)" title="Copiar email">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
            </button>
          </div>
          <div class="contacto-item">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
            <a href="tel:+541155551234" style="color:var(--text);text-decoration:none;">+54 11 5555-1234</a>
          </div>
          <div class="contacto-item">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor" style="color:#25D366;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            <a href="https://wa.me/541155551234?text=Hola%20ShopRive!" target="_blank" rel="noopener" style="color:var(--text);text-decoration:none;">+54 11 5555-1234 (WhatsApp)</a>
            <button class="copy-btn" onclick="copyEmail(this)" title="Copiar número" data-copy="+541155551234">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
            </button>
          </div>
          <div class="contacto-item">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>Lun a Vie 9:00 - 18:00</span>
          </div>
          <div class="contacto-item">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <a href="https://www.google.com/maps/search/?api=1&query=Av.+Corrientes+1234,+Buenos+Aires,+Argentina" target="_blank" rel="noopener" style="color:var(--text);text-decoration:none;">Av. Corrientes 1234, Buenos Aires, Argentina</a>
          </div>
        </div>
        <div class="contacto-social">
          <span>Seguinos en:</span>
          <div class="social-icons">
            <a href="#" class="social-icon" aria-label="Facebook"><svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg></a>
            <a href="#" class="social-icon" aria-label="Instagram"><svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="5"/><circle cx="17.5" cy="6.5" r="1.5"/></svg></a>
            <a href="#" class="social-icon" aria-label="X"><svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
            <a href="https://wa.me/541155551234?text=Hola%20ShopRive!" target="_blank" rel="noopener" class="social-icon" aria-label="WhatsApp"><svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></a>
          </div>
        </div>
      </div>
      <div class="contacto-card">
        <div class="contacto-map">
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d210147.39713853848!2d-58.57338524999999!3d-34.61546195!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95bcca3b4ef90cbd%3A0xa0b3812e88e88e87!2sBuenos%20Aires%2C%20Argentina!5e0!3m2!1ses!2s!4v1" width="100%" height="100%" style="border:0;border-radius:16px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
      </div>
    </div>
    <div class="contacto-chat-cta">
      <canvas id="contacto-chat-rive" width="48" height="48" style="width:48px;height:48px;"></canvas>
      <div>
        <h3>¿Necesitás ayuda?</h3>
        <p>Chateá con nuestro asistente virtual</p>
      </div>
      <button class="btn-primary" onclick="toggleChat()">Iniciar Chat</button>
    </div>
    <div class="contacto-back">
      <a href="#inicio" onclick="showSection('inicio')" class="btn-primary" style="background:var(--bg-card);color:var(--text-muted);border:1px solid var(--border);">
        ← Volver a Inicio
      </a>
    </div>
  </section>

  <!-- CART SIDEBAR -->
  <div class="cart-overlay" id="cart-overlay" onclick="toggleCart()"></div>
  <div class="cart-sidebar" id="cart-sidebar">
    <div class="cart-header">
      <h2>Tu Carrito</h2>
      <button class="icon-btn" onclick="toggleCart()">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <div class="cart-items" id="cart-items">
      <div class="cart-empty">
        <canvas id="empty-cart-rive" width="120" height="120"></canvas>
        <p>Tu carrito está vacío</p>
      </div>
    </div>
    <div class="cart-footer" id="cart-footer">
      <div class="cart-total">
        <span>Total:</span>
        <span id="cart-total">$0</span>
      </div>
      <button class="btn-primary checkout-btn" onclick="checkout()">
        <canvas id="checkout-rive" width="24" height="24" class="checkout-rive-canvas"></canvas>
        Finalizar Compra
      </button>
    </div>
  </div>

  <!-- PAYMENT FORM OVERLAY -->
  <div class="payment-overlay" id="payment-overlay" onclick="closePayment()"></div>
  <div class="payment-modal" id="payment-modal">
    <div class="payment-header">
      <h2>Finalizar Compra</h2>
      <button class="icon-btn" onclick="closePayment()">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <div class="payment-body" id="payment-body">
      <div class="checkout-choice" id="checkout-choice">
        <canvas id="checkout-choice-rive" width="64" height="64" style="width:64px;height:64px;margin:0 auto 16px;display:block;"></canvas>
        <p style="color:var(--text-muted);text-align:center;margin-bottom:24px;">Para continuar con tu compra, elegí una opción:</p>
        <button class="btn-primary" onclick="goRegister()" style="width:100%;margin-bottom:12px;background:transparent;border:2px solid var(--primary);display:flex;flex-direction:column;gap:4px;padding:16px;border-radius:16px;line-height:1.4;">
          <span style="font-size:1rem;">Crear una cuenta</span>
          <span style="font-size:0.8rem;font-weight:400;opacity:0.7;">Guardá tus datos para futuras compras</span>
        </button>
        <button class="btn-primary" onclick="openPaymentForm()" style="width:100%;display:flex;flex-direction:column;gap:4px;padding:16px;border-radius:16px;line-height:1.4;">
          <span style="font-size:1rem;">Comprar como invitado</span>
          <span style="font-size:0.8rem;font-weight:400;opacity:0.7;">Con tarjeta de crédito o débito</span>
        </button>
      </div>
      <div class="payment-form-wrap" id="payment-form-wrap" style="display:none;">
        <div class="payment-resumen" id="payment-resumen"></div>
        <form id="payment-form" onsubmit="submitPayment(event)">
          <div class="payment-field">
            <label>Nombre completo</label>
            <input type="text" id="pay-nombre" required placeholder="Ej: Juan Pérez">
          </div>
          <div class="payment-field">
            <label>Email</label>
            <input type="email" id="pay-email" required placeholder="ej@correo.com">
          </div>
          <div class="payment-row">
            <div class="payment-field">
              <label>Teléfono</label>
              <input type="tel" id="pay-telefono" required placeholder="+54 11 5555-1234">
            </div>
            <div class="payment-field">
              <label>Método de pago</label>
              <select id="pay-metodo" required>
                <option value="tarjeta">Tarjeta de Crédito/Débito</option>
                <option value="qr">QR - Cuenta DNI</option>
                <option value="transferencia">Transferencia Bancaria</option>
                <option value="mercadopago">Mercado Pago</option>
                <option value="efectivo">Efectivo</option>
              </select>
            </div>
          </div>
          <div id="solo-retiro-notice" style="display:none;background:rgba(253,121,168,0.12);border:1px solid var(--accent);border-radius:12px;padding:12px 16px;margin-bottom:12px;font-size:0.9rem;color:var(--accent);font-weight:600;">
            Este producto solo está disponible para retiro en local
          </div>
          <div class="payment-row">
            <div class="payment-field" style="grid-column:1/-1;">
              <label>Forma de envío</label>
              <select id="pay-envio" onchange="toggleEnvio()">
                <option value="domicilio">Envío a domicilio</option>
                <option value="retiro">Retiro en local</option>
              </select>
            </div>
          </div>
          <div id="envio-direccion-wrap">
            <div class="payment-row">
              <div class="payment-field">
                <label>Calle y número</label>
                <input type="text" id="pay-calle" required placeholder="Av. Corrientes 1234">
              </div>
              <div class="payment-field">
                <label>Localidad</label>
                <input type="text" id="pay-localidad" required placeholder="Buenos Aires">
              </div>
            </div>
            <div class="payment-row">
              <div class="payment-field">
                <label>Entre calles (opcional)</label>
                <input type="text" id="pay-entre-calles" placeholder="Ej: Entre Callao y Rodríguez Peña">
              </div>
              <div class="payment-field">
                <label>Coordenadas (opcional)</label>
                <div style="display:flex;gap:8px;">
                  <input type="text" id="pay-coordenadas" placeholder="Ej: -34.6037, -58.3816" style="flex:1;">
                  <button type="button" class="btn-primary" onclick="obtenerUbicacion()" style="padding:12px 16px;font-size:0.8rem;white-space:nowrap;flex-shrink:0;" title="Obtener ubicación">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/></svg>
                  </button>
                </div>
              </div>
            </div>
            <div class="payment-field">
              <label>Comentarios de ubicación (opcional)</label>
              <textarea id="pay-comentarios" placeholder="Ej: Edificio blanco, 2do piso, timbre 3..." rows="2"></textarea>
            </div>
          </div>
          <div class="payment-field">
            <label>Notas del pedido (opcional)</label>
            <textarea id="pay-notas" placeholder="Alguna observación..." rows="2"></textarea>
          </div>
          <button type="submit" class="btn-primary" id="pay-submit-btn" style="width:100%;margin-top:8px;">
            <canvas id="pay-submit-rive" width="24" height="24" style="width:24px;height:24px;display:none;vertical-align:middle;margin-right:8px;"></canvas>
            <span id="pay-submit-text">Confirmar Pedido</span>
          </button>
        </form>
      </div>
      <div class="payment-card-step" id="payment-card-step" style="display:none;">
        <div style="text-align:center;margin-bottom:20px;">
          <canvas id="payment-card-rive" width="56" height="56" style="width:56px;height:56px;margin:0 auto 12px;display:block;"></canvas>
          <h3>Pago con Tarjeta</h3>
          <p style="color:var(--text-muted);font-size:0.9rem;">Pedido #<span id="card-pedido-id"></span> — Total: <strong id="card-total" style="color:var(--accent);"></strong></p>
        </div>
        <form id="card-form" onsubmit="processCardPayment(event)">
          <div class="payment-field">
            <label>Titular de la tarjeta</label>
            <input type="text" id="card-name" required placeholder="Como figura en la tarjeta">
          </div>
          <div class="payment-field">
            <label>Número de tarjeta</label>
            <input type="text" id="card-number" required placeholder="1234 5678 9012 3456" maxlength="19" inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'').replace(/(\d{4})/g,'$1 ').trim().slice(0,19)">
          </div>
          <div class="payment-row">
            <div class="payment-field">
              <label>Vencimiento</label>
              <input type="text" id="card-expiry" required placeholder="MM/AA" maxlength="5" inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'').replace(/^(\d{2})/,'$1/').slice(0,5)">
            </div>
            <div class="payment-field">
              <label>CVV</label>
              <input type="text" id="card-cvv" required placeholder="123" maxlength="4" inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'')">
            </div>
          </div>
          <button type="submit" class="btn-primary" id="card-pay-btn" style="width:100%;margin-top:8px;">
            <span id="card-pay-text">Pagar</span>
          </button>
          <button type="button" class="btn-primary" onclick="showPaymentForm()" style="width:100%;margin-top:8px;background:transparent;border:1px solid var(--border);color:var(--text-muted);font-size:0.85rem;">← Volver a datos de envío</button>
        </form>
      </div>
      <div class="payment-qr-step" id="payment-qr-step" style="display:none;">
        <div style="text-align:center;margin-bottom:20px;">
          <h3>Pagar con Cuenta DNI</h3>
          <p style="color:var(--text-muted);font-size:0.9rem;">Pedido #<span id="qr-pedido-id"></span> — Total: <strong id="qr-total" style="color:var(--accent);"></strong></p>
        </div>
        <div class="qr-wrapper">
          <img id="qr-code-img" src="" alt="Código QR" style="width:220px;height:220px;display:block;margin:0 auto 16px;border-radius:12px;">
          <p style="font-size:0.85rem;color:var(--text-muted);text-align:center;margin-bottom:16px;">Escaneá con tu app <strong>Cuenta DNI</strong></p>
          <div class="qr-details">
            <div class="qr-detail-row"><span class="qr-detail-label">CBU</span><span class="qr-detail-value">0000003100012345678901</span><button class="copy-btn" onclick="copyEmail(this)" title="Copiar CBU" data-copy="0000003100012345678901"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg></button></div>
            <div class="qr-detail-row"><span class="qr-detail-label">Alias</span><span class="qr-detail-value">SHOPRIVE.MERCADO.MIO</span><button class="copy-btn" onclick="copyEmail(this)" title="Copiar alias" data-copy="SHOPRIVE.MERCADO.MIO"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg></button></div>
            <div class="qr-detail-row"><span class="qr-detail-label">Titular</span><span class="qr-detail-value">ShopRive S.A.</span></div>
          </div>
          <p style="font-size:0.8rem;color:var(--text-muted);text-align:center;margin-top:16px;line-height:1.5;">Después de pagar, envianos el comprobante por <a href="https://wa.me/541155551234?text=Hola%20ShopRive!%20Ya%20realic%C3%A9%20el%20pago%20de%20mi%20pedido%20" target="_blank" rel="noopener" style="color:#25D366;">WhatsApp</a> para confirmar tu pedido.</p>
          <button class="btn-primary" onclick="closePayment()" style="margin-top:16px;width:100%;">Cerrar</button>
        </div>
      </div>
      <div class="payment-success" id="payment-success" style="display:none;">
        <!-- Progress bar shown during processing -->
        <div id="payment-progress-wrap" style="display:none;margin-bottom:24px;">
          <div style="font-size:0.9rem;color:var(--text-muted);margin-bottom:8px;text-align:center;" id="payment-progress-text">Procesando pago...</div>
          <div style="width:100%;height:6px;background:var(--bg);border-radius:3px;overflow:hidden;">
            <div id="payment-progress-bar" style="width:0%;height:100%;background:linear-gradient(90deg,#6c5ce7,#fd79a8);border-radius:3px;transition:width 0.5s ease;"></div>
          </div>
        </div>
        <canvas id="payment-success-rive" width="120" height="120" style="width:120px;height:120px;margin:0 auto 16px;display:block;"></canvas>
        <canvas id="payment-confetti-rive" width="300" height="300" style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);width:100vw;height:100vh;pointer-events:none;z-index:300;display:none;"></canvas>
        <h3 style="text-align:center;">¡Compra Exitosa! 🎉</h3>
        <p id="payment-success-msg" style="text-align:center;color:var(--text-muted);margin-top:8px;">Gracias por tu compra. Te enviamos un email con los detalles.</p>
        <button class="btn-primary" onclick="closePayment()" style="margin-top:16px;width:100%;">Seguir Comprando</button>
      </div>
    </div>
  </div>

  <!-- TOAST -->
  <div class="toast" id="toast">
    <canvas id="toast-rive" width="28" height="28" class="toast-rive-canvas"></canvas>
    <span id="toast-message">Producto agregado</span>
  </div>

  <!-- CHAT BOT -->
  <div class="chat-toggle" id="chat-toggle" onclick="toggleChat()">
    <canvas id="chat-toggle-rive" width="60" height="60" style="width:60px;height:60px;"></canvas>
  </div>
  <div class="chat-window" id="chat-window">
    <div class="chat-header">
      <canvas id="chat-rive" width="32" height="32" class="chat-rive-canvas"></canvas>
      <span>Asistente ShopRive</span>
      <button class="icon-btn" onclick="toggleChat()" style="margin-left:auto;">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <div class="chat-messages" id="chat-messages">
      <div class="chat-msg bot">
        <canvas id="chat-avatar-rive" width="28" height="28" class="chat-avatar-canvas"></canvas>
        <div class="chat-bubble">¡Hola! Soy el asistente de ShopRive 🤖 ¿En qué puedo ayudarte?</div>
      </div>
    </div>
    <div class="chat-input-area">
      <input type="text" id="chat-input" placeholder="Escribí un mensaje..." onkeydown="if(event.key==='Enter')sendMessage()">
      <button onclick="sendMessage()" class="chat-send-btn">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
        </svg>
      </button>
    </div>
  </div>

  <script src="js/main.js"></script>
</body>
</html>
