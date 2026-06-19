<?php
/**
 * API Bootstrap - Shared initialization for all API endpoints
 */

// Session
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// Load database
require_once __DIR__ . '/../config/database.php';

// Load infrastructure
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/ErrorHandler.php';
require_once __DIR__ . '/CSRF.php';
require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/Router.php';

// Initialize services
Logger::init();
ErrorHandler::register();
RateLimiter::init();
CSRF::init();

// Content type
header('Content-Type: application/json');

// Rate limiting (skip for OPTIONS)
RateLimiter::middleware();

// DB check
if (!$pdo) {
  Response::error('Error de conexión a la base de datos. ¿Ejecutaste php scripts/setup_db.php?');
}

/**
 * Parse JSON body from request
 */
function getJsonBody(): array {
  $raw = file_get_contents('php://input');
  if (empty($raw)) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}
