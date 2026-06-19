<?php
/**
 * Multi-driver Mailer
 *
 * Supported drivers:
 *   - smtp:     SMTP con autenticación (usando sockets nativos de PHP)
 *   - sendgrid: API HTTP de SendGrid
 *   - resend:   API HTTP de Resend
 *   - log:      Escribe a archivo (para desarrollo/testing)
 *   - mail:     PHP mail() function (fallback por defecto)
 *
 * Configuración en config/mail.php:
 *   return [
 *     'driver'        => 'smtp',
 *     'from_email'    => 'noreply@shoprive.com',
 *     'from_name'     => 'ShopRive',
 *     'smtp_host'     => 'smtp.sendgrid.net',
 *     'smtp_port'     => 587,
 *     'smtp_user'     => 'apikey',
 *     'smtp_pass'     => 'SG.xxxxx',
 *     'smtp_encryption' => 'tls',
 *     'sendgrid_key'  => 'SG.xxxxx',
 *     'resend_key'    => 're_xxxxx',
 *     'log_path'      => __DIR__ . '/../data/mail_log'
 *   ];
 */

class Mailer {
  private static ?array $config = null;
  private static ?Mailer $instance = null;
  private string $driver;

  private const DRIVERS = ['smtp', 'sendgrid', 'resend', 'log', 'mail'];

  public function __construct(array $config = []) {
    self::loadConfig($config);
    $this->driver = self::$config['driver'] ?? 'mail';
  }

  public static function getInstance(): self {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public static function loadConfig(array $override = []): void {
    if (self::$config !== null) return;

    $configFile = __DIR__ . '/../config/mail.php';
    $fileConfig = file_exists($configFile) ? require $configFile : [];

    self::$config = array_merge([
      'driver'        => 'mail',
      'from_email'    => 'noreply@shoprive.com',
      'from_name'     => 'ShopRive',
      'smtp_host'     => 'localhost',
      'smtp_port'     => 25,
      'smtp_user'     => '',
      'smtp_pass'     => '',
      'smtp_encryption' => '',
      'sendgrid_key'  => '',
      'resend_key'    => '',
      'log_path'      => __DIR__ . '/../data/mail_log',
    ], $fileConfig, $override);

    // Auto-detect best available driver if set to 'auto'
    if (self::$config['driver'] === 'auto') {
      self::$config['driver'] = self::detectBestDriver();
    }
  }

  private static function detectBestDriver(): string {
    if (!empty(self::$config['sendgrid_key'])) return 'sendgrid';
    if (!empty(self::$config['resend_key'])) return 'resend';
    if (!empty(self::$config['smtp_host']) && self::$config['smtp_host'] !== 'localhost') return 'smtp';
    return 'mail';
  }

  /**
   * Send an email
   *
   * @param string $to       Recipient email
   * @param string $subject  Email subject
   * @param string $body     HTML body
   * @param array  $options  Optional headers, cc, bcc
   * @return array ['success' => bool, 'message' => string]
   */
  public function send(string $to, string $subject, string $body, array $options = []): array {
    $driver = $this->driver;

    try {
      return match ($driver) {
        'smtp'     => $this->sendSMTP($to, $subject, $body, $options),
        'sendgrid' => $this->sendSendGrid($to, $subject, $body, $options),
        'resend'   => $this->sendResend($to, $subject, $body, $options),
        'log'      => $this->sendLog($to, $subject, $body, $options),
        default    => $this->sendMail($to, $subject, $body, $options),
      };
    } catch (\Exception $e) {
      Logger::error("Mailer ($driver) failed: " . $e->getMessage(), [
        'to' => $to, 'subject' => $subject
      ]);
      // Fallback to mail() if primary driver fails
      if ($driver !== 'mail') {
        Logger::info("Mailer: falling back to mail() for $to");
        return $this->sendMail($to, $subject, $body, $options);
      }
      return ['success' => false, 'message' => $e->getMessage()];
    }
  }

  /**
   * Driver: PHP mail()
   */
  private function sendMail(string $to, string $subject, string $body, array $options = []): array {
    $fromEmail = $options['from_email'] ?? self::$config['from_email'];
    $fromName  = $options['from_name'] ?? self::$config['from_name'];
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $fromName <$fromEmail>\r\n";
    if (!empty($options['cc'])) $headers .= "Cc: {$options['cc']}\r\n";
    $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
    $result = @mail($to, $encodedSubject, $body, $headers);
    if (!$result) {
      Logger::error("mail() failed to send to $to");
      return ['success' => false, 'message' => 'mail() returned false'];
    }
    return ['success' => true, 'message' => 'Sent via mail()'];
  }

  /**
   * Driver: SMTP con autenticación
   */
  private function sendSMTP(string $to, string $subject, string $body, array $options = []): array {
    $host = self::$config['smtp_host'];
    $port = (int)self::$config['smtp_port'];
    $user = self::$config['smtp_user'];
    $pass = self::$config['smtp_pass'];
    $enc  = self::$config['smtp_encryption'];
    $fromEmail = $options['from_email'] ?? self::$config['from_email'];
    $fromName  = $options['from_name'] ?? self::$config['from_name'];

    $socket = fsockopen(
      $enc === 'ssl' ? 'ssl://' . $host : $host,
      $port,
      $errno, $errstr, 15
    );
    if (!$socket) {
      throw new \Exception("SMTP connection failed: $errstr ($errno)");
    }

    $this->smtpCommand($socket, null); // read greeting
    $this->smtpCommand($socket, "EHLO shoprive");

    if ($enc === 'tls') {
      $this->smtpCommand($socket, "STARTTLS");
      stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
      $this->smtpCommand($socket, "EHLO shoprive");
    }

    if ($user && $pass) {
      $this->smtpCommand($socket, "AUTH LOGIN");
      $this->smtpCommand($socket, base64_encode($user));
      $this->smtpCommand($socket, base64_encode($pass));
    }

    $this->smtpCommand($socket, "MAIL FROM:<$fromEmail>");
    $this->smtpCommand($socket, "RCPT TO:<$to>");
    $this->smtpCommand($socket, "DATA");
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $fromName <$fromEmail>\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    $this->smtpCommand($socket, $headers . "\r\n" . $body . "\r\n.");
    $this->smtpCommand($socket, "QUIT");
    fclose($socket);

    return ['success' => true, 'message' => 'Sent via SMTP'];
  }

  private function smtpCommand($socket, ?string $command): string {
    if ($command !== null) {
      fwrite($socket, $command . "\r\n");
    }
    $response = '';
    while ($line = fgets($socket, 512)) {
      $response .= $line;
      if (isset($line[3]) && $line[3] === ' ') break;
    }
    $code = (int)substr($response, 0, 3);
    if ($code >= 400) {
      throw new \Exception("SMTP error: $response");
    }
    return $response;
  }

  /**
   * Driver: SendGrid v3 API
   */
  private function sendSendGrid(string $to, string $subject, string $body, array $options = []): array {
    $apiKey = self::$config['sendgrid_key'];
    if (!$apiKey) throw new \Exception('SendGrid API key not configured');
    $fromEmail = $options['from_email'] ?? self::$config['from_email'];
    $fromName  = $options['from_name'] ?? self::$config['from_name'];

    $payload = json_encode([
      'personalizations' => [['to' => [['email' => $to]]]],
      'from' => ['email' => $fromEmail, 'name' => $fromName],
      'subject' => $subject,
      'content' => [['type' => 'text/html', 'value' => $body]]
    ]);

    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt_array($ch, [
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $payload,
      CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
      ],
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
      throw new \Exception("SendGrid error (HTTP $httpCode): $response");
    }
    return ['success' => true, 'message' => 'Sent via SendGrid'];
  }

  /**
   * Driver: Resend API
   */
  private function sendResend(string $to, string $subject, string $body, array $options = []): array {
    $apiKey = self::$config['resend_key'];
    if (!$apiKey) throw new \Exception('Resend API key not configured');
    $fromEmail = $options['from_email'] ?? self::$config['from_email'];
    $fromName  = $options['from_name'] ?? self::$config['from_name'];

    $payload = json_encode([
      'from' => "$fromName <$fromEmail>",
      'to' => [$to],
      'subject' => $subject,
      'html' => $body,
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $payload,
      CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: ' => 'application/json',
      ],
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
      throw new \Exception("Resend error (HTTP $httpCode): $response");
    }
    return ['success' => true, 'message' => 'Sent via Resend'];
  }

  /**
   * Driver: Log to file (development/testing)
   */
  private function sendLog(string $to, string $subject, string $body, array $options = []): array {
    $logPath = self::$config['log_path'];
    if (!is_dir($logPath)) @mkdir($logPath, 0775, true);
    $filename = $logPath . '/' . date('Y-m-d_H-i-s') . '_' . md5($to . $subject) . '.html';
    $content = "<!-- To: $to | Subject: $subject | Date: " . date('Y-m-d H:i:s') . " -->\n$body";
    file_put_contents($filename, $content);
    Logger::info("Mail logged to $filename", ['to' => $to, 'subject' => $subject]);
    return ['success' => true, 'message' => 'Logged to file'];
  }

  /**
   * Quick helper for one-off sends
   */
  public static function quickSend(string $to, string $subject, string $body): array {
    return self::getInstance()->send($to, $subject, $body);
  }
}
