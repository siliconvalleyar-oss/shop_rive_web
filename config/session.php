<?php
/**
 * Session configuration
 *
 * Driver options: auto, file, redis, memcached
 *
 * 'auto' detecta automáticamente el mejor driver disponible:
 *   1. redis (si php-redis está instalado)
 *   2. memcached (si php-memcached está instalado)
 *   3. file (fallback por defecto)
 *
 * Requisitos:
 *   - redis:  ext-redis (pecl install redis)
 *   - memcached: ext-memcached (pecl install memcached)
 */
return [
  'driver'   => 'auto',
  'lifetime' => 86400 * 7,       // 7 días

  // Para driver 'file': directorio donde guardar las sesiones
  'path'     => __DIR__ . '/../data/sessions',

  // Para driver 'redis'
  'redis'    => [
    'host'   => '127.0.0.1',
    'port'   => 6379,
    'prefix' => 'shoprive_sess:',
  ],

  // Para driver 'memcached'
  'memcached' => [
    'host'   => '127.0.0.1',
    'port'   => 11211,
    'prefix' => 'shoprive_sess:',
  ],
];
