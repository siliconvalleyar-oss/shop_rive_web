/* ============================================
   Checkout / Payment Module
   ============================================ */

import { store } from './state.js';
import { formatPrice, showToast } from './utils.js';
import { loadRive } from './rive.js';
import { updateCart } from './cart.js';
import { renderProducts, renderOfertas } from './products.js';
import { saveCart } from './cart.js';
import { toggleEnvio, updateEnvioNotice } from './checkout-ui.js';
import { buildPaymentResumen } from './checkout-ui.js';

export function checkout() {
  const cart = store.get('cart');
  if (cart.length === 0) { showToast('El carrito está vacío'); return; }

  store.set('pendingPedidoId', null);
  resetSubmitBtn();

  document.getElementById('payment-overlay').classList.add('open');
  document.getElementById('payment-modal').classList.add('open');

  if (store.get('userId')) {
    document.getElementById('checkout-choice').style.display = 'none';
    document.getElementById('payment-form-wrap').style.display = 'block';
    document.getElementById('payment-success').style.display = 'none';
    buildPaymentResumen();
    updateEnvioNotice();
    toggleEnvio();
    fetch('api/auth.php?action=session').then(r => r.json()).then(d => {
      if (d.success && d.user) {
        const nameEl = document.getElementById('pay-nombre');
        const emailEl = document.getElementById('pay-email');
        if (nameEl) nameEl.value = d.user.nombre || '';
        if (emailEl) emailEl.value = d.user.email || '';
      }
    }).catch(() => {});
  } else {
    document.getElementById('checkout-choice').style.display = 'block';
    document.getElementById('payment-form-wrap').style.display = 'none';
    document.getElementById('payment-success').style.display = 'none';
    setTimeout(() => loadRive('checkout-choice-rive', 'assets/riv/buy-button-sparkle.riv'), 100);
  }
}

export function goRegister() {
  window.location.href = 'auth/register.html';
}

export function openPaymentForm() {
  document.getElementById('checkout-choice').style.display = 'none';
  document.getElementById('payment-form-wrap').style.display = 'block';
  document.getElementById('payment-success').style.display = 'none';
  buildPaymentResumen();
  updateEnvioNotice();
  toggleEnvio();
}

export function closePayment() {
  document.getElementById('payment-overlay').classList.remove('open');
  document.getElementById('payment-modal').classList.remove('open');
  document.getElementById('payment-confetti-rive').style.display = 'none';
  setTimeout(() => {
    if (!store.get('userId')) {
      document.getElementById('checkout-choice').style.display = 'block';
    }
    document.getElementById('payment-form-wrap').style.display = 'none';
    document.getElementById('payment-card-step').style.display = 'none';
    document.getElementById('payment-qr-step').style.display = 'none';
    document.getElementById('payment-success').style.display = 'none';
    document.getElementById('payment-progress-wrap').style.display = 'none';
    const cardForm = document.getElementById('card-form');
    if (cardForm) cardForm.style.display = 'block';
  }, 300);
}

export function showPaymentForm() {
  store.set('pendingPedidoId', null);
  resetSubmitBtn();
  document.getElementById('payment-card-step').style.display = 'none';
  document.getElementById('payment-qr-step').style.display = 'none';
  document.getElementById('payment-form-wrap').style.display = 'block';
  document.getElementById('payment-success').style.display = 'none';
}

export function showCardStep(pedidoId) {
  document.getElementById('payment-form-wrap').style.display = 'none';
  document.getElementById('payment-card-step').style.display = 'block';
  document.getElementById('payment-qr-step').style.display = 'none';
  document.getElementById('payment-success').style.display = 'none';
  document.getElementById('card-pedido-id').textContent = pedidoId;
  const cart = store.get('cart');
  const total = cart.reduce((s, i) => s + i.price * i.qty, 0);
  document.getElementById('card-total').textContent = formatPrice(total);
  setTimeout(() => loadRive('payment-card-rive', 'assets/riv/buy-button-sparkle.riv'), 100);
}

export function showQRStep(pedidoId) {
  document.getElementById('payment-form-wrap').style.display = 'none';
  document.getElementById('payment-card-step').style.display = 'none';
  document.getElementById('payment-qr-step').style.display = 'block';
  document.getElementById('payment-success').style.display = 'none';
  document.getElementById('qr-pedido-id').textContent = pedidoId;
  const cart = store.get('cart');
  const total = cart.reduce((s, i) => s + i.price * i.qty, 0);
  document.getElementById('qr-total').textContent = formatPrice(total);
  const data = '0002000100000003100012345678901|SHOPRIVE.MERCADO.MIO|' + total;
  document.getElementById('qr-code-img').src = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(data);
}

export function showPaymentProgress(onComplete) {
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
    if (pct > 95) pct = 95;
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

export function resetSubmitBtn() {
  const btn = document.getElementById('pay-submit-btn');
  if (btn) btn.disabled = false;
  const btnText = document.getElementById('pay-submit-text');
  if (btnText) btnText.textContent = 'Confirmar Pedido';
  const btnRive = document.getElementById('pay-submit-rive');
  if (btnRive) btnRive.style.display = 'none';
}

export async function submitPayment(e) {
  e.preventDefault();

  const pendingPedidoId = store.get('pendingPedidoId');
  if (pendingPedidoId) {
    const metodo = document.getElementById('pay-metodo').value;
    if (metodo === 'qr') {
      showQRStep(pendingPedidoId);
    } else {
      showCardStep(pendingPedidoId);
    }
    return;
  }

  const btn = document.getElementById('pay-submit-btn');
  const btnText = document.getElementById('pay-submit-text');
  const btnRive = document.getElementById('pay-submit-rive');
  btn.disabled = true;
  btnText.textContent = 'Creando pedido...';
  btnRive.style.display = 'inline-block';
  loadRive('pay-submit-rive', 'assets/riv/buy-button-sparkle.riv');

  const cart = store.get('cart');
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
      store.set('pendingPedidoId', data.pedido_id);
      resetSubmitBtn();
      const metodo = document.getElementById('pay-metodo').value;
      if (metodo === 'qr') {
        showQRStep(data.pedido_id);
      } else {
        showCardStep(data.pedido_id);
      }
    } else {
      showToast(data.message || 'Error al crear el pedido');
      resetSubmitBtn();
    }
  } catch (e) {
    showToast('Error de conexión. Intentá de nuevo.');
    resetSubmitBtn();
  }
}

export async function processCardPayment(e) {
  e.preventDefault();
  const btn = document.getElementById('card-pay-btn');
  const btnText = document.getElementById('card-pay-text');
  btn.disabled = true;
  btnText.textContent = 'Procesando...';
  const cardForm = document.getElementById('card-form');
  if (cardForm) cardForm.style.display = 'none';
  const progress = showPaymentProgress();

  const pedidoId = document.getElementById('card-pedido-id').textContent;

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
      store.set('pendingPedidoId', null);
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

      const products = store.get('products');
      const cart = store.get('cart');
      for (const item of cart) {
        const prod = products.find(p => p.id === item.id);
        if (prod) prod.stock = Math.max(0, prod.stock - item.qty);
      }
      if (store.get('userId')) {
        try { await fetch('api/cart.php?action=clear', { method: 'POST' }); } catch (e) {}
      }
      store.set('cart', []);
      saveCart();
      updateCart();
      renderProducts();
      renderOfertas();
    } else {
      showToast(data.message || 'Error al procesar el pago');
      btn.disabled = false;
      btnText.textContent = 'Pagar';
      if (cardForm) cardForm.style.display = 'block';
    }
  } catch (e) {
    showToast('Error de conexión. Intentá de nuevo.');
    btn.disabled = false;
    btnText.textContent = 'Pagar';
    if (cardForm) cardForm.style.display = 'block';
  }
}
