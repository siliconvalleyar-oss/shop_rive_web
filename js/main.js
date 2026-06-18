/* ============================================
   ShopRive - Main JavaScript
   ============================================ */

// --- PRODUCT DATA (cargados desde API con fallback offline) ---
let products = [];
const defaultProducts = [
  { id: 1, name: 'Auriculares Pro', category: 'electronica', price: 45000, riv: 'hero-ui-animation', color: '#6c5ce7', stock: 25, solo_retiro: false, variantes: [] },
  { id: 2, name: 'Reloj Inteligente', category: 'electronica', price: 65000, riv: 'rotating-can', color: '#fd79a8', stock: 15, solo_retiro: false, variantes: [] },
  { id: 3, name: 'Zapatillas Urbanas', category: 'moda', price: 52000, riv: 'shoe-showcase', color: '#00b894', stock: 30, solo_retiro: false, variantes: [] },
  { id: 4, name: 'Bolso de Mano', category: 'moda', price: 38000, riv: 'purse-360', color: '#fdcb6e', stock: 20, solo_retiro: false, variantes: [] },
  { id: 5, name: 'Lámpara LED', category: 'hogar', price: 18000, riv: 'off_road_car_0_6', color: '#e17055', stock: 50, solo_retiro: false, variantes: [] },
  { id: 6, name: 'Campera Premium', category: 'moda', price: 78000, riv: 'shoe-showcase', color: '#00cec9', stock: 12, solo_retiro: false, variantes: [] },
  { id: 7, name: 'Tablet 10"', category: 'electronica', price: 120000, riv: 'rotating-can', color: '#a29bfe', stock: 8, solo_retiro: false, variantes: [] },
  { id: 8, name: 'Set de Pesas', category: 'deportes', price: 35000, riv: 'off_road_car_0_6', color: '#fab1a0', stock: 18, solo_retiro: false, variantes: [] },
  { id: 9, name: 'Billetera Elegante', category: 'moda', price: 22000, riv: 'purse-360', color: '#6c5ce7', stock: 35, solo_retiro: false, variantes: [] },
  { id: 10, name: 'Parlante Portátil', category: 'electronica', price: 32000, riv: 'hero-ui-animation', color: '#fd79a8', stock: 22, solo_retiro: false, variantes: [] },
];

let cart = [];
let currentSlide = 0;
let slideInterval;
let riveInstances = [];
let userId = null;
let userRol = null;
let selectedVariants = {};

// --- UTILS ---
function formatPrice(n) {
  return '$' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// --- RIVE ---
function loadRive(canvasId, src, opts = {}) {
  const el = document.getElementById(canvasId);
  if (!el) return;
  try {
    const r = new rive.Rive({
      src: src,
      canvas: el,
      autoplay: true,
      fit: 'cover',
      ...opts,
      onLoad: () => {
        r.resizeDrawingSurfaceToCanvas();
        if (opts.onLoad) opts.onLoad(r);
      },
    });
    riveInstances.push(r);
    return r;
  } catch (e) {}
}

function initRiveAnimations() {
  loadRive('logo-rive', 'assets/riv/car.riv');
  ['hero-ui-animation.riv', 'shoe-showcase.riv', 'purse-360.riv'].forEach((f, i) => {
    loadRive('hero-rive-' + i, 'assets/riv/' + f);
  });
  ['cat-electronica', 'cat-moda', 'cat-hogar', 'cat-deportes'].forEach((id, i) => {
    loadRive(id, ['assets/riv/hero-ui-animation.riv', 'assets/riv/shoe-showcase.riv', 'assets/riv/rotating-can.riv', 'assets/riv/off_road_car_0_6.riv'][i]);
  });
  loadRive('empty-cart-rive', 'assets/riv/marty.riv');
  loadRive('toast-rive', 'assets/riv/success_check.riv');
  loadRive('checkout-rive', 'assets/riv/buy-button-sparkle.riv');
  loadRive('btn-cta-rive', 'assets/riv/glowing-hover-button.riv');
  loadRive('chat-rive', 'assets/riv/chat-bot.riv');
  loadRive('chat-avatar-rive', 'assets/riv/chat-bot.riv');
  loadRive('chat-toggle-rive', 'assets/riv/chat-icon.riv');
  loadRive('contacto-chat-rive', 'assets/riv/chat-bot.riv');
}

function showSection(id) {
  document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
  const link = document.querySelector(`.nav-link[href="#${id}"]`);
  if (link) link.classList.add('active');

  if (id === 'contacto') {
    document.body.classList.add('show-contacto');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } else {
    document.body.classList.remove('show-contacto');
    const el = document.getElementById(id);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

// --- PRODUCTS ---
async function loadProductsFromAPI() {
  try {
    const res = await fetch('api/products.php');
    const data = await res.json();
    if (data.success && data.productos) {
      products = data.productos.map(p => {
        let variantes = [];
        try { variantes = typeof p.variantes === 'string' ? JSON.parse(p.variantes) : (p.variantes || []); } catch(e) {}
        return {
          id: parseInt(p.id),
          name: p.nombre,
          category: p.categoria,
          price: parseFloat(p.precio),
          riv: p.riv_file || 'car',
          color: p.color || '#6c5ce7',
          stock: parseInt(p.stock) || 0,
          solo_retiro: p.solo_retiro == 1,
          variantes: variantes
        };
      });
      return;
    }
  } catch (e) {}
  products = [...defaultProducts];
}

function renderProducts(filter) {
  const grid = document.getElementById('products-grid');
  const filtered = filter ? products.filter(p => p.category === filter) : products;
  grid.innerHTML = '';
  filtered.forEach((p, i) => {
    const card = document.createElement('div');
    card.className = 'product-card';
    card.style.animationDelay = (i * 0.1) + 's';
    const ext = (p.riv || '').split('.').pop().toLowerCase();
    const isImg = ['png', 'jpg', 'jpeg', 'svg'].includes(ext);
    const mediaHtml = isImg
      ? `<img src="assets/uploads/${p.riv}" style="width:100%;height:200px;object-fit:cover;display:block;">`
      : `<canvas id="prod-rive-${p.id}" width="280" height="200" style="width:100%;height:200px;"></canvas>`;
    card.innerHTML = `
      <div style="height:200px;background:${p.color};display:flex;align-items:center;justify-content:center;border-radius:20px 20px 0 0;overflow:hidden;">
        ${mediaHtml}
      </div>
      <div class="product-info">
        <div class="product-category">${p.category}</div>
        <div class="product-name">${p.name}${p.solo_retiro ? '<span style="font-size:0.65rem;background:var(--accent);color:#fff;padding:2px 8px;border-radius:20px;margin-left:6px;vertical-align:middle;">Local</span>' : ''}</div>
        <div class="product-price">${formatPrice(p.price)}</div>
        <div id="prod-stock-${p.id}" style="font-size:0.8rem;color:${p.stock > 5 ? 'var(--success)' : p.stock > 0 ? 'var(--accent)' : 'var(--text-muted)'};margin-bottom:${p.variantes && p.variantes.length ? '4px' : '12px'};">
          ${p.stock > 0 ? 'Stock: ' + p.stock : 'Sin stock'}
        </div>
        ${p.variantes && p.variantes.length > 0 ? `
          <div style="margin-bottom:8px;">
            <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:4px;" id="prod-colors-${p.id}">
              ${p.variantes.map((v, vi) => `<button type="button" class="color-swatch" data-vi="${vi}" data-color="${v.color}" data-stock="${v.stock}" data-nombre="${v.nombre}" data-modelo="${v.modelo}" style="width:28px;height:28px;border-radius:50%;border:2px solid transparent;background:${v.color};cursor:pointer;padding:0;outline:none;transition:all .15s;" onclick="selectVariant(${p.id}, ${vi})" title="${v.nombre}${v.modelo ? ' - ' + v.modelo : ''}"></button>`).join('')}
            </div>
            <div style="font-size:0.75rem;color:var(--text-muted);" id="prod-variant-label-${p.id}">Seleccioná un color</div>
          </div>
        ` : ''}
        <button class="add-to-cart" id="prod-btn-${p.id}" onclick="addToCart(${p.id})" ${p.stock <= 0 ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : ''}>
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
          </svg>
          ${p.stock > 0 ? 'Agregar al Carrito' : 'Sin Stock'}
        </button>
      </div>
    `;
    grid.appendChild(card);
    if (!isImg) setTimeout(() => loadRive('prod-rive-' + p.id, 'assets/riv/' + p.riv + '.riv'), 100);
  });
}

// --- CAROUSEL ---
function initCarousel() {
  const dots = document.getElementById('carousel-dots');
  for (let i = 0; i < 3; i++) {
    const dot = document.createElement('div');
    dot.className = 'dot' + (i === 0 ? ' active' : '');
    dot.onclick = () => goToSlide(i);
    dots.appendChild(dot);
  }
  startAutoSlide();
}

function goToSlide(index) {
  const slides = document.querySelectorAll('.hero-slide');
  const dots = document.querySelectorAll('.dot');
  slides.forEach((s, i) => s.classList.toggle('active', i === index));
  dots.forEach((d, i) => d.classList.toggle('active', i === index));
  currentSlide = index;
  resetAutoSlide();
}

function nextSlide() { goToSlide((currentSlide + 1) % 3); }
function prevSlide() { goToSlide((currentSlide + 2) % 3); }
function startAutoSlide() { slideInterval = setInterval(nextSlide, 5000); }
function resetAutoSlide() { clearInterval(slideInterval); startAutoSlide(); }

// --- CART (offline-first con localStorage) ---
function toggleCart() {
  document.getElementById('cart-sidebar').classList.toggle('open');
  document.getElementById('cart-overlay').classList.toggle('open');
}

function saveCart() {
  localStorage.setItem('shoprive_cart', JSON.stringify(cart));
}

function loadCart() {
  try {
    const saved = localStorage.getItem('shoprive_cart');
    if (saved) cart = JSON.parse(saved);
  } catch (e) { cart = []; }
}

async function syncCartWithServer() {
  if (!userId) return;
  for (const item of cart) {
    try {
      await fetch('api/cart.php?action=add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ producto_id: item.id, cantidad: item.qty })
      });
    } catch (e) {}
  }
}

function selectVariant(prodId, vi) {
  selectedVariants[prodId] = vi;
  const prod = products.find(p => p.id === prodId);
  if (!prod || !prod.variantes) return;
  const v = prod.variantes[vi];
  // Highlight selected color
  document.querySelectorAll(`#prod-colors-${prodId} .color-swatch`).forEach((btn, i) => {
    btn.style.borderColor = i === vi ? '#fff' : 'transparent';
    btn.style.boxShadow = i === vi ? '0 0 0 2px var(--primary)' : 'none';
  });
  // Show variant label
  const label = document.getElementById('prod-variant-label-' + prodId);
  if (label) label.textContent = v.nombre + (v.modelo ? ' - ' + v.modelo : '') + ' (Stock: ' + v.stock + ')';
  // Update stock display
  const stockEl = document.getElementById('prod-stock-' + prodId);
  if (stockEl) {
    const totalStock = prod.variantes.reduce((s, x) => s + (x.stock || 0), 0);
    stockEl.textContent = 'Stock total: ' + totalStock;
    stockEl.style.color = totalStock > 5 ? 'var(--success)' : totalStock > 0 ? 'var(--accent)' : 'var(--text-muted)';
  }
}

async function addToCart(id) {
  const prod = products.find(p => p.id === id);
  if (!prod) { showToast('Producto no encontrado'); return; }
  const hasVariants = prod.variantes && prod.variantes.length > 0;
  const totalStock = hasVariants ? prod.variantes.reduce((s, v) => s + (v.stock || 0), 0) : prod.stock;
  if (totalStock <= 0) { showToast('Producto sin stock'); return; }

  if (hasVariants && selectedVariants[id] === undefined) {
    showToast('Seleccioná un color primero');
    return;
  }
  const vi = selectedVariants[id];
  const v = vi !== undefined ? prod.variantes[vi] : null;
  const effectiveStock = v ? v.stock : prod.stock;
  if (effectiveStock <= 0) { showToast('Variante sin stock'); return; }

  const existing = cart.find(item => item.id === id && item.variantIdx === vi);
  const qtyInCart = existing ? existing.qty : 0;
  if (qtyInCart >= effectiveStock) { showToast('Stock máximo alcanzado'); return; }

  // Sync with server for stock check if needed
  if (existing) { existing.qty++; }
  else {
    const cartItem = { ...prod, qty: 1, variantIdx: vi };
    if (v) {
      cartItem.selectedColor = v.color;
      cartItem.selectedColorName = v.nombre;
      cartItem.selectedModelo = v.modelo;
    }
    cart.push(cartItem);
  }

  if (userId) {
    try {
      await fetch('api/cart.php?action=add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ producto_id: id, cantidad: 1 })
      });
    } catch (e) {}
  }

  saveCart();
  updateCart();
  showToast('Agregado: ' + prod.name);
  const badge = document.getElementById('cart-badge');
  badge.style.animation = 'none';
  setTimeout(() => badge.style.animation = 'badgePop 0.3s ease', 10);
}

function removeFromCart(id, variantIdx) {
  cart = cart.filter(item => !(item.id === id && item.variantIdx === variantIdx));
  if (userId) fetch('api/cart.php?action=remove', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ producto_id: id })
  }).catch(() => {});
  saveCart();
  updateCart();
}

function updateQty(id, delta, variantIdx) {
  let item;
  if (variantIdx !== undefined) {
    item = cart.find(i => i.id === id && i.variantIdx === variantIdx);
  } else {
    item = cart.find(i => i.id === id);
  }
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) { removeFromCart(id, item.variantIdx); return; }
  if (userId) fetch('api/cart.php?action=update', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ producto_id: id, cantidad: item.qty })
  }).catch(() => {});
  saveCart();
  updateCart();
}

function updateCart() {
  const itemsContainer = document.getElementById('cart-items');
  const totalEl = document.getElementById('cart-total');
  const badge = document.getElementById('cart-badge');
  const totalItems = cart.reduce((sum, i) => sum + i.qty, 0);
  badge.textContent = totalItems;

  if (cart.length === 0) {
    itemsContainer.innerHTML = `
      <div class="cart-empty">
        <canvas id="empty-cart-rive" width="120" height="120"></canvas>
        <p>Tu carrito está vacío</p>
      </div>`;
    loadRive('empty-cart-rive', 'assets/riv/marty.riv');
    totalEl.textContent = '$0';
    return;
  }

  let html = '';
  let total = 0;
  cart.forEach(item => {
    total += item.price * item.qty;
    html += `
      <div class="cart-item">
        <div style="width:64px;height:64px;border-radius:12px;background:${item.selectedColor || item.color};flex-shrink:0;"></div>
        <div class="cart-item-info">
          <div class="cart-item-name">${item.name}${item.selectedColorName ? ' <span style="font-weight:400;color:var(--text-muted);font-size:0.75rem;">(' + item.selectedColorName + (item.selectedModelo ? ' - ' + item.selectedModelo : '') + ')</span>' : ''}</div>
          <div class="cart-item-price">${formatPrice(item.price)}</div>
          <div class="cart-item-qty">
            <button class="qty-btn" onclick="updateQty(${item.id}, -1${item.variantIdx !== undefined ? ',' + item.variantIdx : ''})">−</button>
            <span>${item.qty}</span>
            <button class="qty-btn" onclick="updateQty(${item.id}, 1${item.variantIdx !== undefined ? ',' + item.variantIdx : ''})">+</button>
          </div>
        </div>
        <button class="cart-item-remove" onclick="removeFromCart(${item.id}${item.variantIdx !== undefined ? ',' + item.variantIdx : ''})">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
          </svg>
        </button>
      </div>`;
  });
  itemsContainer.innerHTML = html;
  totalEl.textContent = formatPrice(total);
}

function obtenerUbicacion() {
  if (!navigator.geolocation) { showToast('Geolocalización no disponible'); return; }
  navigator.geolocation.getCurrentPosition(
    pos => {
      document.getElementById('pay-coordenadas').value = pos.coords.latitude + ', ' + pos.coords.longitude;
      showToast('Ubicación obtenida');
    },
    () => showToast('No se pudo obtener la ubicación. Permití el acceso al GPS.')
  );
}

function toggleEnvio() {
  const wrap = document.getElementById('envio-direccion-wrap');
  const envioSelect = document.getElementById('pay-envio');
  const soloRetiro = cart.some(item => item.solo_retiro);
  if (soloRetiro) {
    envioSelect.value = 'retiro';
    envioSelect.disabled = true;
    wrap.style.display = 'none';
  } else {
    envioSelect.disabled = false;
    if (envioSelect.value === 'retiro') {
      wrap.style.display = 'none';
    } else {
      wrap.style.display = 'block';
    }
  }
}

// --- PAYMENT / CHECKOUT ---
function checkout() {
  if (cart.length === 0) { showToast('El carrito está vacío'); return; }

  document.getElementById('payment-overlay').classList.add('open');
  document.getElementById('payment-modal').classList.add('open');

  if (userId) {
    document.getElementById('checkout-choice').style.display = 'none';
    document.getElementById('payment-form-wrap').style.display = 'block';
    document.getElementById('payment-success').style.display = 'none';
    buildPaymentResumen();
    updateEnvioNotice();
    toggleEnvio();
    fetch('api/auth.php?action=session').then(r => r.json()).then(d => {
      if (d.success && d.user) {
        document.getElementById('pay-nombre').value = d.user.nombre || '';
        document.getElementById('pay-email').value = d.user.email || '';
      }
    }).catch(() => {});
  } else {
    document.getElementById('checkout-choice').style.display = 'block';
    document.getElementById('payment-form-wrap').style.display = 'none';
    document.getElementById('payment-success').style.display = 'none';
    setTimeout(() => loadRive('checkout-choice-rive', 'assets/riv/buy-button-sparkle.riv'), 100);
  }
}

function goRegister() {
  window.location.href = 'auth/register.html';
}

function buildPaymentResumen() {
  const el = document.getElementById('payment-resumen');
  let html = '<h3 style="margin-bottom:12px;font-size:1rem;">Resumen del pedido</h3>';
  let total = 0;
  cart.forEach(item => {
    total += item.price * item.qty;
    html += `<div style="display:flex;justify-content:space-between;padding:6px 0;font-size:0.9rem;border-bottom:1px solid var(--border);">
      <span>${item.name} <span style="color:var(--text-muted);">x${item.qty}</span></span>
      <span style="color:var(--accent);font-weight:600;">${formatPrice(item.price * item.qty)}</span>
    </div>`;
  });
  html += `<div style="display:flex;justify-content:space-between;padding:10px 0 0;font-size:1.1rem;font-weight:700;">
    <span>Total</span><span style="color:var(--accent);">${formatPrice(total)}</span>
  </div>`;
  el.innerHTML = html;
}

function openPaymentForm() {
  document.getElementById('checkout-choice').style.display = 'none';
  document.getElementById('payment-form-wrap').style.display = 'block';
  document.getElementById('payment-success').style.display = 'none';
  buildPaymentResumen();
  updateEnvioNotice();
  toggleEnvio();
}

function updateEnvioNotice() {
  const notice = document.getElementById('solo-retiro-notice');
  const soloRetiro = cart.some(item => item.solo_retiro);
  if (soloRetiro) {
    const names = cart.filter(i => i.solo_retiro).map(i => i.name).join(', ');
    notice.textContent = '📦 Estos productos son solo para retiro en local: ' + names;
    notice.style.display = 'block';
  } else {
    notice.style.display = 'none';
  }
}

function closePayment() {
  document.getElementById('payment-overlay').classList.remove('open');
  document.getElementById('payment-modal').classList.remove('open');
  document.getElementById('payment-confetti-rive').style.display = 'none';
  setTimeout(() => {
    if (!userId) {
      document.getElementById('checkout-choice').style.display = 'block';
    }
    document.getElementById('payment-form-wrap').style.display = 'none';
    document.getElementById('payment-card-step').style.display = 'none';
    document.getElementById('payment-qr-step').style.display = 'none';
    document.getElementById('payment-success').style.display = 'none';
    document.getElementById('payment-progress-wrap').style.display = 'none';
    document.getElementById('payment-card-form').style.display = 'block';
  }, 300);
}

function showPaymentForm() {
  document.getElementById('payment-card-step').style.display = 'none';
  document.getElementById('payment-qr-step').style.display = 'none';
  document.getElementById('payment-form-wrap').style.display = 'block';
  document.getElementById('payment-success').style.display = 'none';
}

function showCardStep(pedidoId) {
  document.getElementById('payment-form-wrap').style.display = 'none';
  document.getElementById('payment-card-step').style.display = 'block';
  document.getElementById('payment-qr-step').style.display = 'none';
  document.getElementById('payment-success').style.display = 'none';
  document.getElementById('card-pedido-id').textContent = pedidoId;
  const total = cart.reduce((s, i) => s + i.price * i.qty, 0);
  document.getElementById('card-total').textContent = formatPrice(total);
  setTimeout(() => loadRive('payment-card-rive', 'assets/riv/buy-button-sparkle.riv'), 100);
}

function showQRStep(pedidoId) {
  document.getElementById('payment-form-wrap').style.display = 'none';
  document.getElementById('payment-card-step').style.display = 'none';
  document.getElementById('payment-qr-step').style.display = 'block';
  document.getElementById('payment-success').style.display = 'none';
  document.getElementById('qr-pedido-id').textContent = pedidoId;
  const total = cart.reduce((s, i) => s + i.price * i.qty, 0);
  document.getElementById('qr-total').textContent = formatPrice(total);
  const data = '0002000100000003100012345678901|SHOPRIVE.MERCADO.MIO|' + total;
  document.getElementById('qr-code-img').src = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(data);
}

function showPaymentProgress(onComplete) {
  const wrap = document.getElementById('payment-progress-wrap');
  const bar = document.getElementById('payment-progress-bar');
  const text = document.getElementById('payment-progress-text');
  wrap.style.display = 'block';
  bar.style.width = '0%';
  text.textContent = 'Iniciando pago...';

  let pct = 0;
  let completed = false;
  const steps = [
    { at: 20, msg: 'Verificando datos...' },
    { at: 50, msg: 'Procesando pago...' },
    { at: 80, msg: 'Confirmando transacción...' },
    { at: 100, msg: '¡Pago completado!' }
  ];
  const interval = setInterval(() => {
    if (completed) return;
    pct += 2;
    if (pct > 95) pct = 95; // Don't reach 100% until complete() is called
    bar.style.width = pct + '%';
    const step = steps.find(s => pct >= s.at);
    if (step) text.textContent = step.msg;
  }, 50);

  return {
    complete(callback) {
      completed = true;
      pct = 100;
      bar.style.width = '100%';
      text.textContent = '✅ ¡Completado!';
      clearInterval(interval);
      if (callback) setTimeout(callback, 500);
    }
  };
}

async function submitPayment(e) {
  e.preventDefault();
  const btn = document.getElementById('pay-submit-btn');
  const btnText = document.getElementById('pay-submit-text');
  const btnRive = document.getElementById('pay-submit-rive');
  btn.disabled = true;
  btnText.textContent = 'Creando pedido...';
  btnRive.style.display = 'inline-block';
  loadRive('pay-submit-rive', 'assets/riv/buy-button-sparkle.riv');

  const items = cart.map(i => ({
    id: i.id,
    name: i.name + (i.selectedColorName ? ' (' + i.selectedColorName + (i.selectedModelo ? ' - ' + i.selectedModelo : '') + ')' : ''),
    price: i.price,
    qty: i.qty,
    color: i.selectedColor || '',
    color_name: i.selectedColorName || '',
    modelo: i.selectedModelo || ''
  }));

  try {
    const res = await fetch('api/checkout.php?action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        nombre: document.getElementById('pay-nombre').value,
        email: document.getElementById('pay-email').value,
        telefono: document.getElementById('pay-telefono').value,
        direccion: document.getElementById('pay-calle')?.value || '',
        localidad: document.getElementById('pay-localidad')?.value || '',
        entre_calles: document.getElementById('pay-entre-calles')?.value || '',
        coordenadas: document.getElementById('pay-coordenadas')?.value || '',
        comentarios_ubicacion: document.getElementById('pay-comentarios')?.value || '',
        metodo_pago: document.getElementById('pay-metodo').value,
        tipo_envio: document.getElementById('pay-envio').value,
        notas: document.getElementById('pay-notas').value,
        items: items
      })
    });
    const data = await res.json();
    if (data.success) {
      const metodo = document.getElementById('pay-metodo').value;
      if (metodo === 'qr') {
        showQRStep(data.pedido_id);
      } else {
        showCardStep(data.pedido_id);
      }
    } else {
      showToast(data.message || 'Error al crear el pedido');
      btn.disabled = false;
      btnText.textContent = 'Confirmar Pedido';
      btnRive.style.display = 'none';
    }
  } catch (e) {
    showToast('Error de conexión. Intentá de nuevo.');
    btn.disabled = false;
    btnText.textContent = 'Confirmar Pedido';
    btnRive.style.display = 'none';
  }
}

async function processCardPayment(e) {
  e.preventDefault();
  const btn = document.getElementById('card-pay-btn');
  const btnText = document.getElementById('card-pay-text');
  btn.disabled = true;
  btnText.textContent = 'Procesando...';
  document.getElementById('payment-card-form').style.display = 'none';
  const progress = showPaymentProgress();

  const pedidoId = document.getElementById('card-pedido-id').textContent;
  const items = cart.map(i => ({ id: i.id, name: i.name, price: i.price, qty: i.qty }));

  try {
    const res = await fetch('api/checkout.php?action=pay', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        pedido_id: parseInt(pedidoId),
        card_name: document.getElementById('card-name').value,
        card_number: document.getElementById('card-number').value,
        card_expiry: document.getElementById('card-expiry').value,
        card_cvv: document.getElementById('card-cvv').value
      })
    });
    const data = await res.json();
    if (data.success) {
      // Pago exitoso → completar progreso + animación
      progress.complete(() => {
        document.getElementById('payment-progress-wrap').style.display = 'none';
        document.getElementById('payment-success').style.display = 'block';
        document.getElementById('payment-success-msg').textContent = 'Pedido #' + data.pedido_id + ' confirmado. Te enviamos un email con los detalles.';
        setTimeout(() => loadRive('payment-success-rive', 'assets/riv/success_check.riv'), 100);
        setTimeout(() => {
          const cel = document.getElementById('payment-confetti-rive');
          cel.style.display = 'block';
          loadRive('payment-confetti-rive', 'assets/riv/confetti-celebration.riv');
          setTimeout(() => { cel.style.display = 'none'; }, 4000);
        }, 600);
      });

      for (const item of cart) {
        const prod = products.find(p => p.id === item.id);
        if (prod) prod.stock = Math.max(0, prod.stock - item.qty);
      }
      if (userId) {
        try { await fetch('api/cart.php?action=clear', { method: 'POST' }); } catch (e) {}
      }
      cart = [];
      saveCart();
      updateCart();
      renderProducts();
      renderOfertas();
    } else {
      showToast(data.message || 'Error al procesar el pago');
      btn.disabled = false;
      btnText.textContent = 'Pagar';
    }
  } catch (e) {
    showToast('Error de conexión. Intentá de nuevo.');
    btn.disabled = false;
    btnText.textContent = 'Pagar';
  }
}

async function loadCartFromDB() {
  if (!userId) { loadCart(); updateCart(); return; }
  try {
    const res = await fetch('api/cart.php');
    const data = await res.json();
    if (data.success && data.items && data.items.length > 0) {
      cart = data.items.map(i => {
        const p = products.find(p => p.id === parseInt(i.producto_id)) || {};
        return {
          id: parseInt(i.producto_id),
          name: i.nombre || p.name || 'Producto',
          price: parseFloat(i.precio || p.price || 0),
          color: i.color || p.color || '#6c5ce7',
          qty: parseInt(i.cantidad)
        };
      });
      saveCart();
    } else {
      loadCart();
      if (cart.length > 0) syncCartWithServer();
    }
  } catch (e) {
    loadCart();
  }
  updateCart();
}

// --- TOAST ---
let toastTimeout;
function showToast(msg) {
  const toast = document.getElementById('toast');
  const msgEl = document.getElementById('toast-message');
  msgEl.textContent = msg;
  toast.classList.add('show');
  loadRive('toast-rive', 'assets/riv/success_check.riv');
  clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => toast.classList.remove('show'), 2500);
}

// --- OFERTAS ---
function renderOfertas() {
  const grid = document.getElementById('ofertas-grid');
  if (!grid || products.length === 0) return;
  const discounts = [15, 20, 25, 30, 35, 40];
  const ofertaItems = products.filter(p => p.stock > 5).slice(0, 6).map((p, i) => ({
    ...p,
    discount: discounts[i % discounts.length]
  }));
  grid.innerHTML = '';
  ofertaItems.forEach((p, i) => {
    const discountedPrice = Math.round(p.price * (1 - p.discount / 100));
    const card = document.createElement('div');
    card.className = 'oferta-card';
    card.style.animationDelay = (i * 0.1) + 's';
    const ext = (p.riv || '').split('.').pop().toLowerCase();
    const isImg = ['png', 'jpg', 'jpeg', 'svg'].includes(ext);
    const mediaHtml = isImg
      ? `<img src="assets/uploads/${p.riv}" style="width:100%;height:150px;object-fit:cover;display:block;">`
      : `<canvas id="oferta-rive-${p.id}" width="200" height="150" style="width:100%;height:150px;"></canvas>`;
    card.innerHTML = `
      <div class="oferta-badge">-${p.discount}%</div>
      <div class="oferta-rive-wrap" style="background:${p.color};">
        ${mediaHtml}
      </div>
      <h3>${p.name}${p.solo_retiro ? '<span style="font-size:0.6rem;background:var(--accent);color:#fff;padding:1px 6px;border-radius:20px;margin-left:4px;vertical-align:middle;">Local</span>' : ''}</h3>
      <div class="oferta-prices">
        <span class="oferta-original">${formatPrice(p.price)}</span>
        <span class="oferta-discounted">${formatPrice(discountedPrice)}</span>
      </div>
      <div class="oferta-category">${p.category}</div>
      <button class="add-to-cart" onclick="addToCart(${p.id})">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
          <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
        </svg>
        Agregar
      </button>
    `;
    grid.appendChild(card);
    if (!isImg) setTimeout(() => loadRive('oferta-rive-' + p.id, 'assets/riv/' + p.riv + '.riv'), 150);
  });
}

// --- FILTER ---
function filterProducts(category) {
  renderProducts(category);
  document.querySelector('.products').scrollIntoView({ behavior: 'smooth' });
}

// --- CHAT ---
let chatOpen = false;

function toggleChat() {
  chatOpen = !chatOpen;
  document.getElementById('chat-window').classList.toggle('open', chatOpen);
  if (chatOpen) document.getElementById('chat-input').focus();
}

async function sendMessage() {
  const input = document.getElementById('chat-input');
  const msg = input.value.trim();
  if (!msg) return;
  input.value = '';

  const messages = document.getElementById('chat-messages');

  // User message
  const userDiv = document.createElement('div');
  userDiv.className = 'chat-msg user';
  userDiv.innerHTML = `<div class="chat-bubble">${escapeHtml(msg)}</div>`;
  messages.appendChild(userDiv);

  // Typing indicator
  const typing = document.createElement('div');
  typing.className = 'chat-typing';
  typing.innerHTML = '<span></span><span></span><span></span>';
  messages.appendChild(typing);
  messages.scrollTop = messages.scrollHeight;

  try {
    const res = await fetch('api/chat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ mensaje: msg })
    });
    const data = await res.json();
    typing.remove();

    const botDiv = document.createElement('div');
    botDiv.className = 'chat-msg bot';
    botDiv.innerHTML = `
      <canvas id="chat-avatar-rive" width="28" height="28" class="chat-avatar-canvas"></canvas>
      <div class="chat-bubble">${data.respuesta || 'Disculpa, no entendí.'}</div>`;
    messages.appendChild(botDiv);
    loadRive('chat-avatar-rive', 'assets/riv/chat-bot.riv');

  } catch (e) {
    typing.remove();
    const botDiv = document.createElement('div');
    botDiv.className = 'chat-msg bot';
    botDiv.innerHTML = `
      <canvas id="chat-avatar-rive" width="28" height="28" class="chat-avatar-canvas"></canvas>
      <div class="chat-bubble">Error de conexión. ¿Está funcionando el servidor?</div>`;
    messages.appendChild(botDiv);
    loadRive('chat-avatar-rive', 'assets/riv/chat-bot.riv');
  }

  messages.scrollTop = messages.scrollHeight;
}

function copyEmail(btn) {
  const text = btn.dataset.copy || btn.parentElement.querySelector('span, a').textContent.trim();
  navigator.clipboard.writeText(text).then(() => {
    const orig = btn.innerHTML;
    btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
    setTimeout(() => btn.innerHTML = orig, 2000);
  }).catch(() => {});
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// --- INIT ---
async function checkSession() {
  try {
    const res = await fetch('api/auth.php?action=session');
    const data = await res.json();
    if (data.success && data.user) {
      userId = data.user.id;
      userRol = data.user.rol;
      const el = document.getElementById('auth-placeholder');
      el.innerHTML = `
        <span style="color:var(--text-muted);font-size:0.85rem;">
          ${data.user.nombre}
          <a href="auth/perfil.php" style="color:var(--text-muted);text-decoration:none;margin-left:6px;font-size:0.85rem;" title="Mi Perfil">✎</a>
          ${data.user.rol === 'admin' ? '<a href="admin/index.php" style="color:var(--primary);text-decoration:none;margin-left:6px;">[Admin]</a>' : ''}
          <a href="auth/logout.php" style="color:var(--accent);text-decoration:none;margin-left:8px;">Salir</a>
        </span>`;
    }
  } catch (e) {}
}

document.addEventListener('DOMContentLoaded', async () => {
  await checkSession();
  await loadProductsFromAPI();
  initRiveAnimations();
  renderProducts();
  renderOfertas();
  initCarousel();
  await loadCartFromDB();
});
