/* ============================================
   ShopRive - Main JavaScript
   ============================================ */

// --- PRODUCT DATA (fallback sin DB) ---
const products = [
  { id: 1, name: 'Auriculares Pro', category: 'electronica', price: 45000, riv: 'hero-ui-animation', color: '#6c5ce7' },
  { id: 2, name: 'Reloj Inteligente', category: 'electronica', price: 65000, riv: 'rotating-can', color: '#fd79a8' },
  { id: 3, name: 'Zapatillas Urbanas', category: 'moda', price: 52000, riv: 'shoe-showcase', color: '#00b894' },
  { id: 4, name: 'Bolso de Mano', category: 'moda', price: 38000, riv: 'purse-360', color: '#fdcb6e' },
  { id: 5, name: 'Lámpara LED', category: 'hogar', price: 18000, riv: 'off_road_car_0_6', color: '#e17055' },
  { id: 6, name: 'Campera Premium', category: 'moda', price: 78000, riv: 'shoe-showcase', color: '#00cec9' },
  { id: 7, name: 'Tablet 10"', category: 'electronica', price: 120000, riv: 'rotating-can', color: '#a29bfe' },
  { id: 8, name: 'Set de Pesas', category: 'deportes', price: 35000, riv: 'off_road_car_0_6', color: '#fab1a0' },
  { id: 9, name: 'Billetera Elegante', category: 'moda', price: 22000, riv: 'purse-360', color: '#6c5ce7' },
  { id: 10, name: 'Parlante Portátil', category: 'electronica', price: 32000, riv: 'hero-ui-animation', color: '#fd79a8' },
];

let cart = [];
let currentSlide = 0;
let slideInterval;
let riveInstances = [];
let userId = null;
let userRol = null;

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
  loadRive('chat-rive', 'assets/riv/success_check.riv');
  loadRive('chat-avatar-rive', 'assets/riv/marty.riv');
}

// --- PRODUCTS ---
function renderProducts(filter) {
  const grid = document.getElementById('products-grid');
  const filtered = filter ? products.filter(p => p.category === filter) : products;
  grid.innerHTML = '';
  filtered.forEach((p, i) => {
    const card = document.createElement('div');
    card.className = 'product-card';
    card.style.animationDelay = (i * 0.1) + 's';
    card.innerHTML = `
      <div style="height:200px;background:${p.color};display:flex;align-items:center;justify-content:center;border-radius:20px 20px 0 0;">
        <canvas id="prod-rive-${p.id}" width="280" height="200" style="width:100%;height:200px;"></canvas>
      </div>
      <div class="product-info">
        <div class="product-category">${p.category}</div>
        <div class="product-name">${p.name}</div>
        <div class="product-price">${formatPrice(p.price)}</div>
        <button class="add-to-cart" onclick="addToCart(${p.id})">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
          </svg>
          Agregar al Carrito
        </button>
      </div>
    `;
    grid.appendChild(card);
    setTimeout(() => loadRive('prod-rive-' + p.id, 'assets/riv/' + p.riv + '.riv'), 100);
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

// --- CART ---
function toggleCart() {
  document.getElementById('cart-sidebar').classList.toggle('open');
  document.getElementById('cart-overlay').classList.toggle('open');
}

async function addToCart(id) {
  if (!userId) {
    showToast('Iniciá sesión para agregar al carrito');
    return;
  }

  // API call
  try {
    await fetch('api/cart.php?action=add', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ producto_id: id, cantidad: 1 })
    });
  } catch (e) {}

  const existing = cart.find(item => item.id === id);
  if (existing) { existing.qty++; }
  else { const p = products.find(p => p.id === id); cart.push({ ...p, qty: 1 }); }

  updateCart();
  showToast('Agregado: ' + products.find(p => p.id === id).name);
  const badge = document.getElementById('cart-badge');
  badge.style.animation = 'none';
  setTimeout(() => badge.style.animation = 'badgePop 0.3s ease', 10);
}

function removeFromCart(id) {
  cart = cart.filter(item => item.id !== id);
  if (userId) fetch('api/cart.php?action=remove', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ producto_id: id })
  }).catch(() => {});
  updateCart();
}

function updateQty(id, delta) {
  const item = cart.find(i => i.id === id);
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) { removeFromCart(id); return; }
  if (userId) fetch('api/cart.php?action=update', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ producto_id: id, cantidad: item.qty })
  }).catch(() => {});
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
        <div style="width:64px;height:64px;border-radius:12px;background:${item.color};flex-shrink:0;"></div>
        <div class="cart-item-info">
          <div class="cart-item-name">${item.name}</div>
          <div class="cart-item-price">${formatPrice(item.price)}</div>
          <div class="cart-item-qty">
            <button class="qty-btn" onclick="updateQty(${item.id}, -1)">−</button>
            <span>${item.qty}</span>
            <button class="qty-btn" onclick="updateQty(${item.id}, 1)">+</button>
          </div>
        </div>
        <button class="cart-item-remove" onclick="removeFromCart(${item.id})">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
          </svg>
        </button>
      </div>`;
  });
  itemsContainer.innerHTML = html;
  totalEl.textContent = formatPrice(total);
}

async function checkout() {
  if (!userId) { showToast('Iniciá sesión para comprar'); return; }
  if (cart.length === 0) { showToast('El carrito está vacío'); return; }
  try { await fetch('api/cart.php?action=clear', { method: 'POST' }); } catch (e) {}
  showToast('Compra realizada con éxito 🎉');
  cart = [];
  updateCart();
  setTimeout(toggleCart, 1000);
}

async function loadCartFromDB() {
  if (!userId) return;
  try {
    const res = await fetch('api/cart.php');
    const data = await res.json();
    if (data.success && data.items) {
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
      updateCart();
    }
  } catch (e) {}
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
    loadRive('chat-avatar-rive', 'assets/riv/marty.riv');

  } catch (e) {
    typing.remove();
    const botDiv = document.createElement('div');
    botDiv.className = 'chat-msg bot';
    botDiv.innerHTML = `
      <canvas id="chat-avatar-rive" width="28" height="28" class="chat-avatar-canvas"></canvas>
      <div class="chat-bubble">Error de conexión. ¿Está funcionando el servidor?</div>`;
    messages.appendChild(botDiv);
  }

  messages.scrollTop = messages.scrollHeight;
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
          ${data.user.rol === 'admin' ? '<a href="admin/index.php" style="color:var(--primary);text-decoration:none;">[Admin]</a>' : ''}
          <a href="auth/logout.php" style="color:var(--accent);text-decoration:none;margin-left:8px;">Salir</a>
          <a href="auth/login.html" style="color:var(--text-muted);text-decoration:none;margin-left:8px;font-size:0.85rem;">Cambiar</a>
        </span>`;
    }
  } catch (e) {}
}

document.addEventListener('DOMContentLoaded', async () => {
  await checkSession();
  initRiveAnimations();
  renderProducts();
  initCarousel();
  if (userId) await loadCartFromDB();
});
