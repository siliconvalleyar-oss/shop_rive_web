/* ============================================
   Cart Module
   ============================================ */

import { store } from './state.js';
import { formatPrice, showToast } from './utils.js';
import { loadRive } from './rive.js';

export function saveCart() {
  localStorage.setItem('shoprive_cart', JSON.stringify(store.get('cart')));
}

export function loadCart() {
  try {
    const saved = localStorage.getItem('shoprive_cart');
    if (saved) store.set('cart', JSON.parse(saved));
  } catch (e) {
    store.set('cart', []);
  }
}

export function toggleCart() {
  document.getElementById('cart-sidebar').classList.toggle('open');
  document.getElementById('cart-overlay').classList.toggle('open');
}

export function selectVariant(prodId, vi) {
  store.update('selectedVariants', v => ({ ...v, [prodId]: vi }));
  const products = store.get('products');
  const prod = products.find(p => p.id === prodId);
  if (!prod || !prod.variantes) return;
  const v = prod.variantes[vi];

  document.querySelectorAll(`#prod-colors-${prodId} .color-swatch`).forEach((btn, i) => {
    btn.style.borderColor = i === vi ? '#fff' : 'transparent';
    btn.style.boxShadow = i === vi ? '0 0 0 2px var(--primary)' : 'none';
  });

  const label = document.getElementById('prod-variant-label-' + prodId);
  if (label) label.textContent = v.nombre + (v.modelo ? ' - ' + v.modelo : '') + ' (Stock: ' + v.stock + ')';

  const stockEl = document.getElementById('prod-stock-' + prodId);
  if (stockEl) {
    const totalStock = prod.variantes.reduce((s, x) => s + (x.stock || 0), 0);
    stockEl.textContent = 'Stock total: ' + totalStock;
    stockEl.style.color = totalStock > 5 ? 'var(--success)' : totalStock > 0 ? 'var(--accent)' : 'var(--text-muted)';
  }
}

export async function addToCart(id) {
  const products = store.get('products');
  const prod = products.find(p => p.id === id);
  if (!prod) { showToast('Producto no encontrado'); return; }

  const hasVariants = prod.variantes && prod.variantes.length > 0;
  const totalStock = hasVariants ? prod.variantes.reduce((s, v) => s + (v.stock || 0), 0) : prod.stock;
  if (totalStock <= 0) { showToast('Producto sin stock'); return; }

  const selectedVariants = store.get('selectedVariants');
  if (hasVariants && selectedVariants[id] === undefined) {
    showToast('Seleccioná un color primero');
    return;
  }

  const vi = selectedVariants[id];
  const v = vi !== undefined ? prod.variantes[vi] : null;
  const effectiveStock = v ? v.stock : prod.stock;
  if (effectiveStock <= 0) { showToast('Variante sin stock'); return; }

  const cart = store.get('cart');
  const existing = cart.find(item => item.id === id && item.variantIdx === vi);
  const qtyInCart = existing ? existing.qty : 0;
  if (qtyInCart >= effectiveStock) { showToast('Stock máximo alcanzado'); return; }

  if (existing) {
    existing.qty++;
  } else {
    const cartItem = { ...prod, qty: 1, variantIdx: vi };
    if (v) {
      cartItem.selectedColor = v.color;
      cartItem.selectedColorName = v.nombre;
      cartItem.selectedModelo = v.modelo;
    }
    cart.push(cartItem);
  }
  store.set('cart', cart);

  if (store.get('userId')) {
    try {
      await fetch('api/cart.php?action=add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ producto_id: id, cantidad: 1 })
      });
    } catch (e) { /* silent */ }
  }

  saveCart();
  updateCart();
  showToast('Agregado: ' + prod.name);
  const badge = document.getElementById('cart-badge');
  if (badge) {
    badge.style.animation = 'none';
    setTimeout(() => badge.style.animation = 'badgePop 0.3s ease', 10);
  }
}

export function removeFromCart(id, variantIdx) {
  const cart = store.get('cart').filter(item => !(item.id === id && item.variantIdx === variantIdx));
  store.set('cart', cart);
  if (store.get('userId')) {
    fetch('api/cart.php?action=remove', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ producto_id: id })
    }).catch(() => {});
  }
  saveCart();
  updateCart();
}

export function updateQty(id, delta, variantIdx) {
  const cart = store.get('cart');
  let item;
  if (variantIdx !== undefined) {
    item = cart.find(i => i.id === id && i.variantIdx === variantIdx);
  } else {
    item = cart.find(i => i.id === id);
  }
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) {
    removeFromCart(id, item.variantIdx);
    return;
  }
  if (store.get('userId')) {
    fetch('api/cart.php?action=update', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ producto_id: id, cantidad: item.qty })
    }).catch(() => {});
  }
  saveCart();
  updateCart();
}

export function updateCart() {
  const itemsContainer = document.getElementById('cart-items');
  const totalEl = document.getElementById('cart-total');
  const badge = document.getElementById('cart-badge');
  if (!itemsContainer || !totalEl || !badge) return;

  const cart = store.get('cart');
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

export async function syncCartWithServer() {
  if (!store.get('userId')) return;
  const cart = store.get('cart');
  for (const item of cart) {
    try {
      await fetch('api/cart.php?action=add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ producto_id: item.id, cantidad: item.qty })
      });
    } catch (e) { /* silent */ }
  }
}

export async function loadCartFromDB() {
  if (!store.get('userId')) {
    loadCart();
    updateCart();
    return;
  }
  try {
    const res = await fetch('api/cart.php');
    const data = await res.json();
    if (data.success && data.items && data.items.length > 0) {
      const products = store.get('products');
      const cartItems = data.items.map(i => {
        const p = products.find(p => p.id === parseInt(i.producto_id)) || {};
        return {
          id: parseInt(i.producto_id),
          name: i.nombre || p.name || 'Producto',
          price: parseFloat(i.precio || p.price || 0),
          color: i.color || p.color || '#6c5ce7',
          qty: parseInt(i.cantidad)
        };
      });
      store.set('cart', cartItems);
      saveCart();
    } else {
      loadCart();
      if (store.get('cart').length > 0) syncCartWithServer();
    }
  } catch (e) {
    loadCart();
  }
  updateCart();
}

export function obtenerUbicacion() {
  if (!navigator.geolocation) { showToast('Geolocalización no disponible'); return; }
  navigator.geolocation.getCurrentPosition(
    pos => {
      document.getElementById('pay-coordenadas').value = pos.coords.latitude + ', ' + pos.coords.longitude;
      showToast('Ubicación obtenida');
    },
    () => showToast('No se pudo obtener la ubicación. Permití el acceso al GPS.')
  );
}

export function toggleEnvio() {
  const wrap = document.getElementById('envio-direccion-wrap');
  const envioSelect = document.getElementById('pay-envio');
  const cart = store.get('cart');
  const soloRetiro = cart.some(item => item.solo_retiro);
  if (soloRetiro) {
    envioSelect.value = 'retiro';
    envioSelect.disabled = true;
    wrap.style.display = 'none';
  } else {
    envioSelect.disabled = false;
    wrap.style.display = envioSelect.value === 'retiro' ? 'none' : 'block';
  }
}
