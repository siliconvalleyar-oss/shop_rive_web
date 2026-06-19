<?php

class Router {
  private array $routes = [];
  private array $middleware = [];
  private string $prefix = '';

  public function group(string $prefix, callable $callback): self {
    $previous = $this->prefix;
    $this->prefix .= $prefix;
    $callback($this);
    $this->prefix = $previous;
    return $this;
  }

  public function addMiddleware(callable $middleware): self {
    $this->middleware[] = $middleware;
    return $this;
  }

  public function get(string $path, $handler): self {
    return $this->addRoute('GET', $path, $handler);
  }

  public function post(string $path, $handler): self {
    return $this->addRoute('POST', $path, $handler);
  }

  public function put(string $path, $handler): self {
    return $this->addRoute('PUT', $path, $handler);
  }

  public function delete(string $path, $handler): self {
    return $this->addRoute('DELETE', $path, $handler);
  }

  private function addRoute(string $method, string $path, $handler): self {
    $this->routes[] = [
      'method' => $method,
      'path' => $this->prefix . $path,
      'handler' => $handler,
      'pattern' => $this->buildPattern($this->prefix . $path)
    ];
    return $this;
  }

  private function buildPattern(string $path): string {
    // Convert {param} to named capture groups
    $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
    return '#^' . $pattern . '$#';
  }

  public function dispatch(string $method, string $uri): void {
    // Remove query string from URI
    $uri = parse_url($uri, PHP_URL_PATH);
    // Remove trailing slash
    $uri = rtrim($uri, '/') ?: '/';

    // Run global middleware
    foreach ($this->middleware as $mw) {
      $mw();
    }

    $method = strtoupper($method);

    foreach ($this->routes as $route) {
      if ($route['method'] !== $method) continue;

      if (preg_match($route['pattern'], $uri, $matches)) {
        // Extract named params
        $params = array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);

        if (is_string($route['handler'])) {
          // Format: "Controller@method"
          [$class, $method_name] = explode('@', $route['handler']);
          if (class_exists($class)) {
            $controller = new $class();
            $controller->$method_name($params);
            return;
          }
        } elseif (is_callable($route['handler'])) {
          call_user_func($route['handler'], $params);
          return;
        }
      }
    }

    // No route matched
    Response::notFound('Ruta no encontrada: ' . $method . ' ' . $uri);
  }

  public function handleRequest(): void {
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];
    $this->dispatch($method, $uri);
  }
}
