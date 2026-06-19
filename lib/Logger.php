<?php

class Logger {
  private static string $logDir = '';
  private static bool $enabled = true;

  public static function init(string $logDir = null) {
    self::$logDir = $logDir ?: __DIR__ . '/../data/logs';
    if (!is_dir(self::$logDir)) {
      @mkdir(self::$logDir, 0775, true);
    }
  }

  public static function enable(bool $state = true) {
    self::$enabled = $state;
  }

  public static function info(string $message, array $context = []) {
    self::write('INFO', $message, $context);
  }

  public static function warning(string $message, array $context = []) {
    self::write('WARNING', $message, $context);
  }

  public static function error(string $message, array $context = []) {
    self::write('ERROR', $message, $context);
  }

  public static function debug(string $message, array $context = []) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
      self::write('DEBUG', $message, $context);
    }
  }

  private static function write(string $level, string $message, array $context = []) {
    if (!self::$enabled) return;
    try {
      self::init();
      $date = date('Y-m-d H:i:s');
      $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
      $method = $_SERVER['REQUEST_METHOD'] ?? '';
      $uri = $_SERVER['REQUEST_URI'] ?? '';
      $ctx = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
      $line = "[$date] [$level] [$ip] $method $uri — $message$ctx" . PHP_EOL;
      $filename = self::$logDir . '/app-' . date('Y-m-d') . '.log';
      file_put_contents($filename, $line, FILE_APPEND | LOCK_EX);
    } catch (\Exception $e) {
      // Silently fail - don't break the app if logging fails
    }
  }
}
