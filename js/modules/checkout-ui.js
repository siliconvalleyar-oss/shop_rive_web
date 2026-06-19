/* ============================================
   Checkout UI helpers (separated from logic)
   ============================================ */

import { store } from './state.js';
import { formatPrice } from './utils.js';

export function buildPaymentResumen() {
  const el = document.getElementById('payment-resumen');
  if (!el) return;
  const cart = store.get('cart');
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

export function updateEnvioNotice() {
  const notice = document.getElementById('solo-retiro-notice');
  if (!notice) return;
  const cart = store.get('cart');
  const soloRetiro = cart.some(item => item.solo_retiro);
  if (soloRetiro) {
    const names = cart.filter(i => i.solo_retiro).map(i => i.name).join(', ');
    notice.textContent = '📦 Estos productos son solo para retiro en local: ' + names;
    notice.style.display = 'block';
  } else {
    notice.style.display = 'none';
  }
}

export function toggleEnvio() {
  const wrap = document.getElementById('envio-direccion-wrap');
  const envioSelect = document.getElementById('pay-envio');
  if (!wrap || !envioSelect) return;
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
