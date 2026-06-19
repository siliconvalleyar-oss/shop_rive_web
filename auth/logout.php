<?php
require_once __DIR__ . '/../lib/SessionManager.php';
require_once __DIR__ . '/../lib/Logger.php';
Logger::init();
SessionManager::init();
session_start();
session_destroy();
header('Location: ../index.php');
exit;
