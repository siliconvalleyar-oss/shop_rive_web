<?php
/**
 * API Bootstrap - Shared initialization for all API endpoints
 */

// CORS headers (must be before session for OPTIONS preflight)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// Load infrastructure
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/ErrorHandler.php';
require_once __DIR__ . '/CSRF.php';
require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/Mailer.php';

// Initialize services (order matters)
Logger::init();
ErrorHandler::register();

// Session — uses configured driver (auto/file/redis/memcached)
SessionManager::init();

RateLimiter::init();
CSRF::init();

// Load database
require_once __DIR__ . '/../config/database.php';

// Content type
header('Content-Type: application/json');

// Rate limiting
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
