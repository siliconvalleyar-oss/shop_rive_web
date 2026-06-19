<?php
/**
 * Mail configuration
 *
 * Driver options: auto, smtp, sendgrid, resend, log, mail
 *
 * Para producción, configurar al menos uno de los siguientes:
 *   - smtp:     setear smtp_host, smtp_user, smtp_pass
 *   - sendgrid: setear sendgrid_key
 *   - resend:   setear resend_key
 *
 * 'auto' detecta automáticamente el mejor driver disponible
 * basado en las credenciales configuradas.
 */
return [
  'driver'        => 'mail',

  'from_email'    => 'noreply@shoprive.com',
  'from_name'     => 'ShopRive',

  // --- SMTP ---
  'smtp_host'     => 'smtp.sendgrid.net',
  'smtp_port'     => 587,
  'smtp_user'     => '',
  'smtp_pass'     => '',
  'smtp_encryption' => 'tls',

  // --- SendGrid ---
  'sendgrid_key'  => '',

  // --- Resend ---
  'resend_key'    => '',

  // --- Log (development) ---
  'log_path'      => __DIR__ . '/../data/mail_log',
];
