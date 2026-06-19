<?php

class RateLimiter {
  private static string $dataFile = '';
  private static int $maxRequests = 60;
  private static int $windowSeconds = 60;

  public static function init(int $maxRequests = 60, int $windowSeconds = 60) {
    self::$dataFile = __DIR__ . '/../data/rate_limits.json';
    self::$maxRequests = $maxRequests;
    self::$windowSeconds = $windowSeconds;
  }

  public static function getClientId(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
  }

  public static function check(int $maxRequests = null, int $windowSeconds = null): bool {
    $maxRequests = $maxRequests ?? self::$maxRequests;
    $windowSeconds = $windowSeconds ?? self::$windowSeconds;
    $clientId = self::getClientId();
    $now = time();

    try {
      $data = [];
      if (file_exists(self::$dataFile)) {
        $data = json_decode(file_get_contents(self::$dataFile), true) ?: [];
      }

      // Clean expired entries
      $data = array_filter($data, fn($entry) => $entry['reset_at'] > $now);

      if (!isset($data[$clientId])) {
        $data[$clientId] = [
          'count' => 1,
          'reset_at' => $now + $windowSeconds
        ];
        self::save($data);
        return true;
      }

      $entry = &$data[$clientId];

      if ($entry['reset_at'] <= $now) {
        $entry['count'] = 1;
        $entry['reset_at'] = $now + $windowSeconds;
        self::save($data);
        return true;
      }

      if ($entry['count'] >= $maxRequests) {
        return false; // Rate limited
      }

      $entry['count']++;
      self::save($data);
      return true;

    } catch (\Exception $e) {
      return true; // Allow on failure (don't block if file is corrupted)
    }
  }

  public static function middleware(int $maxRequests = null, int $windowSeconds = null) {
    if (!self::check($maxRequests, $windowSeconds)) {
      Logger::warning('Rate limit exceeded', ['client' => self::getClientId()]);
      Response::error('Demasiadas solicitudes. Intentá de nuevo en unos segundos.', 429);
    }
  }

  private static function save(array $data) {
    @file_put_contents(self::$dataFile, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
  }
}
