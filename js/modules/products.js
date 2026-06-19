/* ============================================
   Products Module
   ============================================ */

import { store } from './state.js';
import { formatPrice } from './utils.js';
import { loadRive } from './rive.js';

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

export async function loadProductsFromAPI() {
  try {
    const res = await fetch('api/products.php');
    const data = await res.json();
    if (data.success && data.productos) {
      const products = data.productos.map(p => {
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
      store.set('products', products);
      return;
    }
  } catch (e) { /* silent */ }
  store.set('products', [...defaultProducts]);
}

export function renderProducts(filter) {
  const grid = document.getElementById('products-grid');
  if (!grid) return;
  const products = store.get('products');
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

export function renderOfertas() {
  const grid = document.getElementById('ofertas-grid');
  const products = store.get('products');
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

export function filterProducts(category) {
  renderProducts(category);
  const el = document.querySelector('.products');
  if (el) el.scrollIntoView({ behavior: 'smooth' });
}
