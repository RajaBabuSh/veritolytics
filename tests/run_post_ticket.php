<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'localhost:8000';
$_SERVER['HTTP_REFERER'] = 'http://localhost:8000/#contact';
require_once __DIR__ . '/../includes/csrf.php';

$_POST = [
    'csrf_token' => generate_csrf_token(),
    'name' => 'Sarah Connor',
    'email' => 'sarah_connor_' . time() . '@example.com',
    'subject' => 'Survey Credit Inquiry',
    'message' => 'Need help with survey credits please.',
    'priority' => 'normal'
];

require __DIR__ . '/../submit_ticket.php';
