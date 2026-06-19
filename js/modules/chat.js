/* ============================================
   Chat Module
   ============================================ */

import { store } from './state.js';
import { escapeHtml } from './utils.js';
import { loadRive } from './rive.js';

export function toggleChat() {
  const chatOpen = store.get('chatOpen');
  store.set('chatOpen', !chatOpen);
  document.getElementById('chat-window').classList.toggle('open', !chatOpen);
  if (!chatOpen) document.getElementById('chat-input').focus();
}

export async function sendMessage() {
  const input = document.getElementById('chat-input');
  const msg = input.value.trim();
  if (!msg) return;
  input.value = '';

  const messages = document.getElementById('chat-messages');

  const userDiv = document.createElement('div');
  userDiv.className = 'chat-msg user';
  userDiv.innerHTML = `<div class="chat-bubble">${escapeHtml(msg)}</div>`;
  messages.appendChild(userDiv);

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
    loadRive('chat-avatar-rive', 'assets/riv/chat-bot.riv');
  } catch (e) {
    typing.remove();
    const botDiv = document.createElement('div');
    botDiv.className = 'chat-msg bot';
    botDiv.innerHTML = `
      <canvas id="chat-avatar-rive" width="28" height="28" class="chat-avatar-canvas"></canvas>
      <div class="chat-bubble">Error de conexión. ¿Está funcionando el servidor?</div>`;
    messages.appendChild(botDiv);
    loadRive('chat-avatar-rive', 'assets/riv/chat-bot.riv');
  }

  messages.scrollTop = messages.scrollHeight;
}

export function copyEmail(btn) {
  const text = btn.dataset.copy || btn.parentElement.querySelector('span, a').textContent.trim();
  navigator.clipboard.writeText(text).then(() => {
    const orig = btn.innerHTML;
    btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
    setTimeout(() => btn.innerHTML = orig, 2000);
  }).catch(() => {});
}
