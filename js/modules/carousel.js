/* ============================================
   Carousel Module
   ============================================ */

import { store } from './state.js';

export function initCarousel() {
  const dots = document.getElementById('carousel-dots');
  if (!dots) return;
  for (let i = 0; i < 3; i++) {
    const dot = document.createElement('div');
    dot.className = 'dot' + (i === 0 ? ' active' : '');
    dot.onclick = () => goToSlide(i);
    dots.appendChild(dot);
  }
  startAutoSlide();
}

export function goToSlide(index) {
  const slides = document.querySelectorAll('.hero-slide');
  const dots = document.querySelectorAll('.dot');
  slides.forEach((s, i) => s.classList.toggle('active', i === index));
  dots.forEach((d, i) => d.classList.toggle('active', i === index));
  store.set('currentSlide', index);
  resetAutoSlide();
}

export function nextSlide() { goToSlide((store.get('currentSlide') + 1) % 3); }
export function prevSlide() { goToSlide((store.get('currentSlide') + 2) % 3); }

export function startAutoSlide() {
  const interval = setInterval(nextSlide, 5000);
  store.set('slideInterval', interval);
}

export function resetAutoSlide() {
  clearInterval(store.get('slideInterval'));
  startAutoSlide();
}
