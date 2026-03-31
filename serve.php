<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_start();

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/app/helpers/AppHelper.php';
apply_security_headers(true);
reject_cross_origin_preflight();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Authentication required.');
}

if (!isset($_GET['asset']) || !isset($_GET['f'])) {
    http_response_code(404);
    exit;
}

$type = (string)$_GET['asset'];
$filename = (string)$_GET['f'];

$path = match ($type) {
    'avatar' => BASE_PATH . '/assets/uploads/avatars/' . basename($filename),
    'post' => BASE_PATH . '/assets/uploads/posts/' . basename($filename),
    'cover' => BASE_PATH . '/assets/uploads/covers/' . basename($filename),
    default => null,
};

if ($path === null || !is_file($path)) {
    http_response_code(404);
    exit;
}

stream_uploaded_asset($path, $type === 'cover' ? 2592000 : 604800, true);
