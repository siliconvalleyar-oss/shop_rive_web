<?php

class Validator {
  private array $data;
  private array $errors = [];
  private array $labels = [];

  public function __construct(array $data, array $labels = []) {
    $this->data = $data;
    $this->labels = $labels;
  }

  public function label(string $field, string $label): self {
    $this->labels[$field] = $label;
    return $this;
  }

  private function getLabel(string $field): string {
    return $this->labels[$field] ?? $field;
  }

  public function required(string ...$fields): self {
    foreach ($fields as $field) {
      $value = $this->data[$field] ?? '';
      if (is_string($value) && trim($value) === '') {
        $this->errors[] = $this->getLabel($field) . ' es requerido';
      } elseif ($value === null || $value === '') {
        $this->errors[] = $this->getLabel($field) . ' es requerido';
      } elseif (is_array($value) && empty($value)) {
        $this->errors[] = $this->getLabel($field) . ' es requerido';
      }
    }
    return $this;
  }

  public function email(string $field): self {
    $value = $this->data[$field] ?? '';
    if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
      $this->errors[] = $this->getLabel($field) . ' no es un email válido';
    }
    return $this;
  }

  public function minLength(string $field, int $min): self {
    $value = $this->data[$field] ?? '';
    if (mb_strlen($value) < $min && $value !== '') {
      $this->errors[] = $this->getLabel($field) . ' debe tener al menos ' . $min . ' caracteres';
    }
    return $this;
  }

  public function maxLength(string $field, int $max): self {
    $value = $this->data[$field] ?? '';
    if (mb_strlen($value) > $max) {
      $this->errors[] = $this->getLabel($field) . ' debe tener máximo ' . $max . ' caracteres';
    }
    return $this;
  }

  public function numeric(string $field): self {
    $value = $this->data[$field] ?? '';
    if ($value !== '' && !is_numeric($value)) {
      $this->errors[] = $this->getLabel($field) . ' debe ser un número';
    }
    return $this;
  }

  public function inArray(string $field, array $allowed): self {
    $value = $this->data[$field] ?? '';
    if ($value !== '' && !in_array($value, $allowed, true)) {
      $this->errors[] = $this->getLabel($field) . ' no es un valor válido';
    }
    return $this;
  }

  public function regex(string $field, string $pattern, string $message = null): self {
    $value = $this->data[$field] ?? '';
    if ($value !== '' && !preg_match($pattern, $value)) {
      $this->errors[] = $message ?: $this->getLabel($field) . ' no tiene un formato válido';
    }
    return $this;
  }

  public function min(string $field, float $min): self {
    $value = $this->data[$field] ?? 0;
    if (is_numeric($value) && floatval($value) < $min) {
      $this->errors[] = $this->getLabel($field) . ' debe ser mayor o igual a ' . $min;
    }
    return $this;
  }

  public function max(string $field, float $max): self {
    $value = $this->data[$field] ?? 0;
    if (is_numeric($value) && floatval($value) > $max) {
      $this->errors[] = $this->getLabel($field) . ' debe ser menor o igual a ' . $max;
    }
    return $this;
  }

  public function passes(): bool {
    return empty($this->errors);
  }

  public function errors(): array {
    return $this->errors;
  }

  public function firstError(): string {
    return $this->errors[0] ?? '';
  }

  public function validateOrFail(): array {
    if (!$this->passes()) {
      throw new ValidationException($this->firstError());
    }
    return $this->data;
  }
}
