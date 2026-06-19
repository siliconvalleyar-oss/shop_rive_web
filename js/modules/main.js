/* ============================================
   ShopRive - Main Entry Point
   Imports all modules and registers global functions
   for backward compatibility with inline onclick handlers.
   ============================================ */

import { store } from './state.js';
import { formatPrice, escapeHtml, showToast } from './utils.js';
import { loadRive, initRiveAnimations } from './rive.js';
import {
  saveCart, loadCart, toggleCart, selectVariant,
  addToCart, removeFromCart, updateQty, updateCart,
  syncCartWithServer, loadCartFromDB, obtenerUbicacion
} from './cart.js';
import { loadProductsFromAPI, renderProducts, renderOfertas, filterProducts } from './products.js';
import {
  checkout, goRegister, openPaymentForm, closePayment,
  showPaymentForm, showCardStep, showQRStep, showPaymentProgress,
  resetSubmitBtn, submitPayment, processCardPayment
} from './checkout.js';
import { toggleEnvio, updateEnvioNotice, buildPaymentResumen } from './checkout-ui.js';
import { toggleChat, sendMessage, copyEmail } from './chat.js';
import { initCarousel, nextSlide, prevSlide, goToSlide } from './carousel.js';

// --- Register globals for inline event handlers ---
window.formatPrice = formatPrice;
window.showToast = showToast;
window.loadRive = loadRive;
window.toggleCart = toggleCart;
window.selectVariant = selectVariant;
window.addToCart = addToCart;
window.removeFromCart = removeFromCart;
window.updateQty = updateQty;
window.checkout = checkout;
window.goRegister = goRegister;
window.openPaymentForm = openPaymentForm;
window.closePayment = closePayment;
window.showPaymentForm = showPaymentForm;
window.submitPayment = submitPayment;
window.processCardPayment = processCardPayment;
window.toggleEnvio = toggleEnvio;
window.obtenerUbicacion = obtenerUbicacion;
window.toggleChat = toggleChat;
window.sendMessage = sendMessage;
window.copyEmail = copyEmail;
window.nextSlide = nextSlide;
window.prevSlide = prevSlide;
window.filterProducts = filterProducts;

// --- State change listeners ---
store.on('userId', (userId) => {
  const authPlaceholder = document.getElementById('auth-placeholder');
  if (!authPlaceholder) return;
  const userRol = store.get('userRol');
  if (userId) {
    // Re-fetch session to get latest data
    fetch('api/auth.php?action=session').then(r => r.json()).then(d => {
      if (d.success && d.user) {
        authPlaceholder.innerHTML = `
          <span style="color:var(--text-muted);font-size:0.85rem;">
            ${d.user.nombre}
            <a href="auth/perfil.php" style="color:var(--text-muted);text-decoration:none;margin-left:6px;font-size:0.85rem;" title="Mi Perfil">✎</a>
            ${d.user.rol === 'admin' ? '<a href="admin/index.php" style="color:var(--primary);text-decoration:none;margin-left:6px;">[Admin]</a>' : ''}
            <a href="auth/logout.php" style="color:var(--accent);text-decoration:none;margin-left:8px;">Salir</a>
          </span>`;
      }
    }).catch(() => {});
  }
});

// --- Session check on page load ---
async function checkSession() {
  try {
    const res = await fetch('api/auth.php?action=session');
    const data = await res.json();
    if (data.success && data.user) {
      store.set('userId', data.user.id);
      store.set('userRol', data.user.rol);
    }
  } catch (e) { /* silent */ }
}

// --- INIT ---
document.addEventListener('DOMContentLoaded', async () => {
  await checkSession();
  await loadProductsFromAPI();
  initRiveAnimations();
  renderProducts();
  renderOfertas();
  initCarousel();
  await loadCartFromDB();
});
