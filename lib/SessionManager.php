<?php
/**
 * Session Manager — Configurable session handler
 *
 * Soporta múltiples backends:
 *   - file:      Sesiones en archivos (default PHP)
 *   - redis:     Sesiones en Redis (requiere extensión php-redis)
 *   - memcached: Sesiones en Memcached (requiere extensión php-memcached)
 *
 * Configuración en config/session.php:
 *   return [
 *     'driver'      => 'auto',          // auto|file|redis|memcached
 *     'lifetime'    => 86400 * 7,       // 7 días
 *     'path'        => '/tmp/shoprive_sessions',  // solo para file
 *     'redis'       => ['host' => '127.0.0.1', 'port' => 6379, 'prefix' => 'sess:'],
 *     'memcached'   => ['host' => '127.0.0.1', 'port' => 11211, 'prefix' => 'sess:'],
 *   ];
 */

class SessionManager {
  private static bool $initialized = false;
  private static array $config = [];

  public static function init(): void {
    if (self::$initialized) return;

    $configFile = __DIR__ . '/../config/session.php';
    self::$config = file_exists($configFile) ? require $configFile : [];

    self::$config = array_merge([
      'driver'   => 'auto',
      'lifetime' => 86400 * 7,
      'path'     => sys_get_temp_dir() . '/shoprive_sessions',
      'redis'    => ['host' => '127.0.0.1', 'port' => 6379, 'prefix' => 'sess:'],
      'memcached' => ['host' => '127.0.0.1', 'port' => 11211, 'prefix' => 'sess:'],
    ], self::$config);

    $driver = self::$config['driver'];
    if ($driver === 'auto') {
      $driver = self::detectBestDriver();
    }

    // Set session config
    ini_set('session.gc_maxlifetime', self::$config['lifetime']);
    ini_set('session.cookie_lifetime', self::$config['lifetime']);
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '0'); // set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    switch ($driver) {
      case 'redis':
        self::initRedis();
        break;
      case 'memcached':
        self::initMemcached();
        break;
      default:
        self::initFile();
        break;
    }

    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    self::$initialized = true;
    Logger::debug("Session manager initialized (driver: $driver)");
  }

  private static function detectBestDriver(): string {
    if (extension_loaded('redis')) return 'redis';
    if (extension_loaded('memcached')) return 'memcached';
    return 'file';
  }

  private static function initFile(): void {
    $path = self::$config['path'];
    if (!is_dir($path)) {
      @mkdir($path, 0775, true);
    }
    session_save_path($path);
  }

  private static function initRedis(): void {
    $cfg = self::$config['redis'];
    try {
      $redis = new Redis();
      $redis->connect($cfg['host'], (int)$cfg['port'], 2.5);
      $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
      $redis->setOption(Redis::OPT_PREFIX, $cfg['prefix'] ?? 'sess:');

      $handler = new class($redis, self::$config['lifetime']) implements SessionHandlerInterface {
        private Redis $redis;
        private int $lifetime;

        public function __construct(Redis $redis, int $lifetime) {
          $this->redis = $redis;
          $this->lifetime = $lifetime;
        }

        public function open(string $path, string $name): bool { return true; }
        public function close(): bool { return true; }

        public function read(string $id): string {
          return $this->redis->get($id) ?: '';
        }

        public function write(string $id, string $data): bool {
          return $this->redis->setex($id, $this->lifetime, $data);
        }

        public function destroy(string $id): bool {
          return $this->redis->del($id) > 0;
        }

        public function gc(int $max_lifetime): int {
          return 0; // Redis handles TTL internally
        }
      };

      session_set_save_handler($handler, true);
      Logger::info('Session handler set to Redis');
    } catch (\Exception $e) {
      Logger::warning("Redis session init failed, falling back to file: " . $e->getMessage());
      self::initFile();
    }
  }

  private static function initMemcached(): void {
    $cfg = self::$config['memcached'];
    try {
      $memcached = new Memcached();
      $memcached->addServer($cfg['host'], (int)$cfg['port']);
      $memcached->setOption(Memcached::OPT_PREFIX_KEY, $cfg['prefix'] ?? 'sess:');

      $handler = new class($memcached, self::$config['lifetime']) implements SessionHandlerInterface {
        private Memcached $memcached;
        private int $lifetime;

        public function __construct(Memcached $memcached, int $lifetime) {
          $this->memcached = $memcached;
          $this->lifetime = $lifetime;
        }

        public function open(string $path, string $name): bool { return true; }
        public function close(): bool { return true; }

        public function read(string $id): string {
          $data = $this->memcached->get($id);
          return $data !== false ? $data : '';
        }

        public function write(string $id, string $data): bool {
          return $this->memcached->set($id, $data, $this->lifetime);
        }

        public function destroy(string $id): bool {
          return $this->memcached->delete($id) > 0;
        }

        public function gc(int $max_lifetime): int {
          return 0; // Memcached handles expiry internally
        }
      };

      session_set_save_handler($handler, true);
      Logger::info('Session handler set to Memcached');
    } catch (\Exception $e) {
      Logger::warning("Memcached session init failed, falling back to file: " . $e->getMessage());
      self::initFile();
    }
  }

  /**
   * Regenerate session ID (call after login for security)
   */
  public static function regenerate(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_regenerate_id(true);
    }
  }

  /**
   * Destroy session
   */
  public static function destroy(): void {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_destroy();
    }
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
      $params['path'], $params['domain'],
      $params['secure'], $params['httponly']
    );
  }

  /**
   * Get config value
   */
  public static function getConfig(string $key, mixed $default = null): mixed {
    return self::$config[$key] ?? $default;
  }
}
