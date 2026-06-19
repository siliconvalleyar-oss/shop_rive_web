/* ============================================
   Utilities
   ============================================ */

import { store } from './state.js';

export function formatPrice(n) {
  return '$' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

export function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

export function showToast(msg) {
  const toast = document.getElementById('toast');
  const msgEl = document.getElementById('toast-message');
  if (!toast || !msgEl) return;
  msgEl.textContent = msg;
  toast.classList.add('show');
  import('./rive.js').then(({ loadRive }) => loadRive('toast-rive', 'assets/riv/success_check.riv'));
  clearTimeout(store.get('toastTimeout'));
  store.set('toastTimeout', setTimeout(() => toast.classList.remove('show'), 2500));
}

export function $id(id) {
  return document.getElementById(id);
}

export function $val(id) {
  const el = $id(id);
  return el ? el.value : '';
}

export function debounce(fn, ms = 300) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), ms);
  };
}
