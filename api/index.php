<?php
/**
 * API Front Controller
 * Entry point for all REST API requests.
 *
 * Usage:
 *   GET    /api/products
 *   GET    /api/products/{id}
 *   POST   /api/auth/login
 *   POST   /api/auth/register
 *   GET    /api/auth/session
 *   POST   /api/auth/logout
 *   GET    /api/cart
 *   POST   /api/cart/add
 *   POST   /api/cart/update
 *   POST   /api/cart/remove
 *   POST   /api/cart/clear
 *   POST   /api/checkout
 *   POST   /api/checkout/{id}/pay
 *   POST   /api/chat
 *   POST   /api/presupuesto
 *
 * Legacy format api/file.php?action=X still works for backward compatibility.
 */

require_once __DIR__ . '/../lib/bootstrap.php';

$router = new Router();

// --- Auth Routes ---
$router->post('/api/auth/register', function() {
  require __DIR__ . '/auth.php';
  handleRegister();
});

$router->post('/api/auth/login', function() {
  require __DIR__ . '/auth.php';
  handleLogin();
});

$router->get('/api/auth/session', function() {
  require __DIR__ . '/auth.php';
  handleSession();
});

$router->post('/api/auth/logout', function() {
  require __DIR__ . '/auth.php';
  handleLogout();
});

// --- Cart Routes ---
$router->get('/api/cart', function() {
  require __DIR__ . '/cart.php';
  handleGetCart();
});

$router->post('/api/cart/add', function() {
  require __DIR__ . '/cart.php';
  handleAddToCart();
});

$router->post('/api/cart/update', function() {
  require __DIR__ . '/cart.php';
  handleUpdateCart();
});

$router->post('/api/cart/remove', function() {
  require __DIR__ . '/cart.php';
  handleRemoveFromCart();
});

$router->post('/api/cart/clear', function() {
  require __DIR__ . '/cart.php';
  handleClearCart();
});

// --- Checkout Routes ---
$router->post('/api/checkout', function() {
  require __DIR__ . '/checkout.php';
  handleCreateOrder();
});

$router->post('/api/checkout/{id}/pay', function(array $params) {
  require __DIR__ . '/checkout.php';
  handlePayOrder((int)$params['id']);
});

// --- Products Routes ---
$router->get('/api/products', function() {
  require __DIR__ . '/products.php';
  handleGetProducts();
});

// --- Chat Routes ---
$router->post('/api/chat', function() {
  require __DIR__ . '/chat.php';
  handleChat();
});

// --- Presupuesto Routes ---
$router->post('/api/presupuesto', function() {
  require __DIR__ . '/presupuesto.php';
  handlePresupuesto();
});

// Dispatch
$router->handleRequest();
