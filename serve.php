<?php


if (!isset($_GET['asset']) || !isset($_GET['f'])) {
    http_response_code(404);
    exit;
}

$type     = $_GET['asset'];
$filename = basename($_GET['f']); 

if ($type === 'avatar') {
    $path = __DIR__ . '/assets/uploads/avatars/' . $filename;
} elseif ($type === 'post') {
    $path = __DIR__ . '/assets/uploads/posts/' . $filename;
} else {
    http_response_code(404);
    exit;
}

if (!file_exists($path)) {
    http_response_code(404);
    exit;
}

$mime = mime_content_type($path);
$allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mime, $allowed_mimes)) {
    http_response_code(403);
    exit;
}

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=86400');
readfile($path);
exit;
