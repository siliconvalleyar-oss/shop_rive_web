<?php

class ErrorHandler {
  private static bool $registered = false;

  public static function register() {
    if (self::$registered) return;
    self::$registered = true;

    set_error_handler([self::class, 'handleError']);
    set_exception_handler([self::class, 'handleException']);
  }

  public static function handleError($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;

    Logger::warning("PHP Error: $message", [
      'severity' => $severity,
      'file' => $file,
      'line' => $line
    ]);

    if (defined('APP_DEBUG') && APP_DEBUG) {
      Response::error("$message in $file:$line", 500);
    } else {
      Response::error('Error interno del servidor', 500);
    }
    return true;
  }

  public static function handleException($e) {
    Logger::error($e->getMessage(), [
      'file' => $e->getFile(),
      'line' => $e->getLine(),
      'trace' => $e->getTraceAsString()
    ]);

    if ($e instanceof ValidationException) {
      Response::error($e->getMessage(), 422, ['field' => $e->getField()]);
    }

    if (defined('APP_DEBUG') && APP_DEBUG) {
      Response::error($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), 500);
    } else {
      Response::error('Error interno del servidor', 500);
    }
  }
}

class ValidationException extends Exception {
  private string $field;
  public function __construct(string $message, string $field = '') {
    parent::__construct($message);
    $this->field = $field;
  }
  public function getField(): string {
    return $this->field;
  }
}
