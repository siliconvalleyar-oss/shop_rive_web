/* ============================================
   State Management
   ============================================ */

export const store = {
  _state: {
    products: [],
    cart: [],
    userId: null,
    userRol: null,
    selectedVariants: {},
    currentSlide: 0,
    slideInterval: null,
    riveInstances: [],
    pendingPedidoId: null,
    chatOpen: false,
    toastTimeout: null,
  },

  _listeners: {},

  get(key) {
    return this._state[key];
  },

  set(key, value) {
    const old = this._state[key];
    this._state[key] = value;
    this._notify(key, value, old);
  },

  update(key, updater) {
    const old = this._state[key];
    const next = updater(old);
    this._state[key] = next;
    this._notify(key, next, old);
  },

  on(key, callback) {
    if (!this._listeners[key]) this._listeners[key] = [];
    this._listeners[key].push(callback);
    return () => {
      this._listeners[key] = this._listeners[key].filter(fn => fn !== callback);
    };
  },

  _notify(key, value, old) {
    if (this._listeners[key]) {
      this._listeners[key].forEach(fn => fn(value, old));
    }
  },

  // Convenience getters
  get cart() { return this._state.cart; },
  get userId() { return this._state.userId; },
  get products() { return this._state.products; },
};
