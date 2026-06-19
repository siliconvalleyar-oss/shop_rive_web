/* ============================================
   Rive Animation Helpers
   ============================================ */

import { store } from './state.js';

export function loadRive(canvasId, src, opts = {}) {
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
    const instances = store.get('riveInstances');
    instances.push(r);
    store.set('riveInstances', instances);
    return r;
  } catch (e) { /* silent */ }
}

export function initRiveAnimations() {
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
