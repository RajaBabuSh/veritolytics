<?php
$sessionId = $argv[1] ?? '';
$tokenToVerify = $argv[2] ?? '';

if (empty($sessionId)) {
    echo "NO_SESSION_ID";
    exit(1);
}

$_COOKIE['PHPSESSID'] = $sessionId;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'localhost:8000';

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/csrf.php';

$loadedUser = $_SESSION['veritolytics_user'] ?? '';
$loadedToken = $_SESSION['csrf_token'] ?? '';
$isValid = verify_csrf_token($tokenToVerify) ? 'VALID' : 'INVALID';

echo "USER:{$loadedUser}|TOKEN_MATCH:" . ($loadedToken === $tokenToVerify ? 'YES' : 'NO') . "|VERIFY:{$isValid}";
