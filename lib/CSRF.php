<?php

class CSRF {
  public static function init() {
    if (empty($_SESSION['_csrf_token'])) {
      $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
      $_SESSION['_csrf_time'] = time();
    }
  }

  public static function getToken(): string {
    self::init();
    return $_SESSION['_csrf_token'];
  }

  public static function getMetaTag(): string {
    return '<meta name="csrf-token" content="' . self::getToken() . '">';
  }

  public static function validate(?string $token): bool {
    self::init();
    if (empty($token)) return false;
    if (empty($_SESSION['_csrf_token'])) return false;
    return hash_equals($_SESSION['_csrf_token'], $token);
  }

  public static function validateOrFail(?string $token): void {
    if (!self::validate($token)) {
      Logger::warning('CSRF validation failed');
      Response::error('Token de seguridad inválido. Recargá la página e intentá de nuevo.', 403);
    }
  }

  public static function refresh() {
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['_csrf_time'] = time();
  }

  public static function middleware() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
      $headers = getallheaders();
      $token = $headers['X-CSRF-Token'] ?? $_POST['_csrf_token'] ?? null;
      self::validateOrFail($token);
    }
  }
}
