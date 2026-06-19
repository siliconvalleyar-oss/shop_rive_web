<?php
require_once 'config/email.php';
$start = microtime(true);
echo "Testing enviarEmailTipo...\n";
enviarEmailTipo('pago_confirmado', 'test@example.com', [
    'id' => 1,
    'nombre' => 'Test',
    'total' => 100,
    'tipo_envio' => 'retiro',
    'metodo_pago' => 'tarjeta'
], []);
echo "Time taken: " . (microtime(true) - $start) . " seconds\n";
