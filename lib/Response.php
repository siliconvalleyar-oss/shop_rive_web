<?php

class Response {
  public static function json($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
  }

  public static function success($data = [], $message = 'OK') {
    self::json(array_merge(['success' => true, 'message' => $message], $data));
  }

  public static function error($message = 'Error', $status = 400, $extra = []) {
    self::json(array_merge(['success' => false, 'message' => $message], $extra), $status);
  }

  public static function created($data = []) {
    self::json(array_merge(['success' => true], $data), 201);
  }

  public static function notFound($message = 'Recurso no encontrado') {
    self::error($message, 404);
  }

  public static function methodNotAllowed() {
    self::error('Método no permitido', 405);
  }
}
