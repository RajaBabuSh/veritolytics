<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'localhost:8000';
require_once __DIR__ . '/../includes/csrf.php';

$testEmail = 'john_connor_' . time() . '@example.com';
$_POST = [
    'csrf_token' => generate_csrf_token(),
    'first_name' => 'John',
    'last_name' => 'Connor',
    'email' => $testEmail,
    'country' => 'United States',
    'password' => 'SecurePass987!',
    'confirm_password' => 'SecurePass987!',
    'agree_terms' => '1'
];

require __DIR__ . '/../auth/register.php';
