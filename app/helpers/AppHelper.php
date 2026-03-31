<?php

if (!function_exists('app_boot_optimizations')) {
    function app_boot_optimizations(): void {
        if (!headers_sent() && extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
            @ini_set('zlib.output_compression', '1');
        }
    }
}

if (!function_exists('apply_security_headers')) {
    function apply_security_headers(bool $cacheHtml = false): void {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('Cross-Origin-Resource-Policy: same-origin');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Origin-Agent-Cluster: ?1');
        header(
            "Content-Security-Policy: default-src 'self'; " .
            "base-uri 'self'; frame-ancestors 'self'; object-src 'none'; form-action 'self'; " .
            "img-src 'self' data: blob: https://cdnjs.cloudflare.com; " .
            "media-src 'self' data: blob:; connect-src 'self'; " .
            "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
            "font-src 'self' data: https://cdnjs.cloudflare.com https://fonts.gstatic.com;"
        );

        if ($cacheHtml) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
}

if (!function_exists('app_strlen')) {
    function app_strlen(string $value): int {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}

if (!function_exists('app_substr')) {
    function app_substr(string $value, int $start, ?int $length = null): string {
        if (function_exists('mb_substr')) {
            return $length === null ? mb_substr($value, $start, null, 'UTF-8') : mb_substr($value, $start, $length, 'UTF-8');
        }
        return $length === null ? substr($value, $start) : substr($value, $start, $length);
    }
}

if (!function_exists('app_strtolower')) {
    function app_strtolower(string $value): string {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }
}

if (!function_exists('app_normalize_single_line')) {
    function app_normalize_single_line(string $value, int $maxLen = 255): string {
        $value = str_replace(["\r", "\n", "\t"], ' ', $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $value) ?? '';
        $value = trim((string)(preg_replace('/\s{2,}/u', ' ', $value) ?? $value));
        return app_substr($value, 0, max(0, $maxLen));
    }
}

if (!function_exists('app_normalize_multiline')) {
    function app_normalize_multiline(string $value, int $maxLen = 1000): string {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $value) ?? '';
        $value = trim($value);
        return app_substr($value, 0, max(0, $maxLen));
    }
}

if (!function_exists('app_current_origin')) {
    function app_current_origin(): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = preg_replace('/[^A-Za-z0-9.:-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . ($host ?: 'localhost');
    }
}

if (!function_exists('app_origin_matches_current')) {
    function app_origin_matches_current(string $originOrUrl): bool {
        $parts = parse_url(trim($originOrUrl));
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        $origin = strtolower($parts['scheme']) . '://' . strtolower($parts['host']);
        if (!empty($parts['port'])) {
            $origin .= ':' . (int)$parts['port'];
        }

        return hash_equals(strtolower(app_current_origin()), $origin);
    }
}

if (!function_exists('ensure_same_origin_request')) {
    function ensure_same_origin_request(): void {
        $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
        if ($origin !== '') {
            if (!app_origin_matches_current($origin)) {
                http_response_code(403);
                exit('Cross-origin request blocked.');
            }
            return;
        }

        $referer = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));
        if ($referer !== '' && !app_origin_matches_current($referer)) {
            http_response_code(403);
            exit('Cross-origin request blocked.');
        }
    }
}

if (!function_exists('reject_cross_origin_preflight')) {
    function reject_cross_origin_preflight(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'OPTIONS') {
            return;
        }

        $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
        if ($origin !== '' && !app_origin_matches_current($origin)) {
            http_response_code(403);
            exit('Cross-origin request blocked.');
        }

        header('Allow: GET, POST, OPTIONS');
        http_response_code(204);
        exit;
    }
}

if (!function_exists('format_chat_time')) {
    function format_chat_time(string $datetime): string {
        try {
            return (new DateTime($datetime))->format('g:i A');
        } catch (Throwable $e) {
            return $datetime;
        }
    }
}

if (!function_exists('asset_version')) {
    function asset_version(string $relativePath): string {
        $relativePath = '/' . ltrim($relativePath, '/');
        $fullPath = BASE_PATH . $relativePath;
        return is_file($fullPath) ? (string)filemtime($fullPath) : (string)time();
    }
}

if (!function_exists('app_rate_limit')) {
    function app_rate_limit(string $key, int $limit, int $windowSeconds): bool {
        if (!isset($_SESSION['_rate_limits']) || !is_array($_SESSION['_rate_limits'])) {
            $_SESSION['_rate_limits'] = [];
        }

        $now = time();
        $bucket = $_SESSION['_rate_limits'][$key] ?? [];
        if (!is_array($bucket)) {
            $bucket = [];
        }

        $bucket = array_values(array_filter($bucket, static function ($timestamp) use ($now, $windowSeconds) {
            return is_int($timestamp) && $timestamp > ($now - $windowSeconds);
        }));

        if (count($bucket) >= $limit) {
            $_SESSION['_rate_limits'][$key] = $bucket;
            return false;
        }

        $bucket[] = $now;
        $_SESSION['_rate_limits'][$key] = $bucket;
        return true;
    }
}

if (!function_exists('uploaded_file_mime')) {
    function uploaded_file_mime(string $path): ?string {
        if (!is_file($path)) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }
        $mime = finfo_file($finfo, $path) ?: null;
        finfo_close($finfo);
        return $mime;
    }
}

if (!function_exists('create_image_resource')) {
    function create_image_resource(string $path, string $mime) {
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }
}

if (!function_exists('save_image_resource')) {
    function save_image_resource($image, string $dest, string $mime, int $quality = 76): bool {
        return match ($mime) {
            'image/jpeg' => imagejpeg($image, $dest, max(55, min(88, $quality))),
            'image/png' => imagepng($image, $dest, 6),
            'image/gif' => imagegif($image, $dest),
            'image/webp' => function_exists('imagewebp') ? imagewebp($image, $dest, max(55, min(88, $quality))) : false,
            default => false,
        };
    }
}

if (!function_exists('optimized_image_upload')) {
    function optimized_image_upload(
        array $file,
        string $folder,
        string $prefix,
        array $options = []
    ): ?string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if (($file['size'] ?? 0) <= 0 || ($file['size'] ?? 0) > (($options['max_file_size'] ?? 5) * 1024 * 1024)) {
            return null;
        }
        if (!is_uploaded_file($file['tmp_name'] ?? '')) {
            return null;
        }

        $tmpPath = $file['tmp_name'];
        $mime = uploaded_file_mime($tmpPath);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        if ($mime === null || !isset($allowed[$mime])) {
            return null;
        }

        $dir = BASE_PATH . '/assets/uploads/' . trim($folder, '/') . '/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }

        $maxWidth = (int)($options['max_width'] ?? 1600);
        $maxHeight = (int)($options['max_height'] ?? 1600);
        $quality = (int)($options['quality'] ?? 76);
        $stripAnimation = !empty($options['strip_animation']);

        $imageInfo = @getimagesize($tmpPath);
        if ($imageInfo === false) {
            return null;
        }

        [$width, $height] = $imageInfo;
        $extension = $allowed[$mime];
        $filename = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $dest = $dir . $filename;

        $canProcess = extension_loaded('gd') && function_exists('imagecreatetruecolor');
        $hasAnimatedGif = ($mime === 'image/gif' && !$stripAnimation);

        if (!$canProcess || $hasAnimatedGif) {
            return move_uploaded_file($tmpPath, $dest) ? $filename : null;
        }

        $src = create_image_resource($tmpPath, $mime);
        if (!$src) {
            return move_uploaded_file($tmpPath, $dest) ? $filename : null;
        }

        $scale = min(1, $maxWidth / max(1, $width), $maxHeight / max(1, $height));
        $targetW = max(1, (int)round($width * $scale));
        $targetH = max(1, (int)round($height * $scale));

        $dst = imagecreatetruecolor($targetW, $targetH);
        if (in_array($mime, ['image/png', 'image/gif', 'image/webp'], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $width, $height);
        $saved = save_image_resource($dst, $dest, $mime, $quality);
        imagedestroy($dst);
        imagedestroy($src);

        if ($saved) {
            return $filename;
        }

        if (is_file($dest)) {
            @unlink($dest);
        }
        return move_uploaded_file($tmpPath, $dest) ? $filename : null;
    }
}

if (!function_exists('remove_uploaded_asset')) {
    function remove_uploaded_asset(?string $type, ?string $filename): void {
        if (!$type || !$filename || $filename === 'default.png') {
            return;
        }
        $path = asset_upload_path($type, $filename);
        if ($path && is_file($path)) {
            @unlink($path);
        }
    }
}

if (!function_exists('stream_uploaded_asset')) {
    function stream_uploaded_asset(string $path, int $maxAge = 604800, bool $privateCache = false): void {
        $mime = mime_content_type($path) ?: 'application/octet-stream';
        $allowed = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'audio/webm', 'audio/ogg', 'audio/mpeg', 'audio/mp4', 'audio/wav', 'audio/x-wav'
        ];
        if (!in_array($mime, $allowed, true)) {
            http_response_code(403);
            exit;
        }

        $etag = '"' . sha1($path . '|' . (string)filesize($path) . '|' . (string)filemtime($path)) . '"';
        header('Content-Type: ' . $mime);
        if ($privateCache) {
            if ($maxAge <= 0) {
                header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
                header('Pragma: no-cache');
                header('Expires: 0');
            } else {
                header('Cache-Control: private, max-age=' . max(0, $maxAge));
            }
        } else {
            header('Cache-Control: public, max-age=' . max(3600, $maxAge) . ', immutable');
        }
        header('ETag: ' . $etag);
        header('Content-Length: ' . (string)filesize($path));
        header('Accept-Ranges: bytes');

        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim((string)$_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
            http_response_code(304);
            exit;
        }

        readfile($path);
        exit;
    }
}


if (!function_exists('app_time_ago')) {
    function app_time_ago(string $datetime): string {
        try {
            $now = new DateTime();
            $ago = new DateTime($datetime);
            $diff = $now->getTimestamp() - $ago->getTimestamp();
            if ($diff < 60) return 'just now';
            if ($diff < 3600) return floor($diff / 60) . 'm ago';
            if ($diff < 86400) return floor($diff / 3600) . 'h ago';
            if ($diff < 604800) return floor($diff / 86400) . 'd ago';
            return $ago->format('M j');
        } catch (Throwable $e) {
            return $datetime;
        }
    }
}

if (!function_exists('friend_action_button')) {
    function friend_action_button(int $otherUserId, string $state, bool $compact = false): string {
        if ($otherUserId <= 0 || $state === 'self') {
            return '';
        }

        $btnClass = $compact ? 'btn btn-sm' : 'btn';
        $label = 'Add Friend';
        $icon = 'fa-solid fa-user-plus';
        $page = 'friend.request';

        if ($state === 'accepted') {
            $label = 'Friends';
            $icon = 'fa-solid fa-user-check';
            $page = 'friend.remove';
            $btnClass .= ' btn-ghost';
        } elseif ($state === 'incoming_pending') {
            $label = 'Accept';
            $icon = 'fa-solid fa-user-check';
            $page = 'friend.accept';
            $btnClass .= ' btn-primary';
        } elseif ($state === 'outgoing_pending') {
            $label = 'Requested';
            $icon = 'fa-regular fa-clock';
            $page = 'friend.remove';
            $btnClass .= ' btn-ghost';
        } else {
            $btnClass .= ' btn-primary';
        }

        return '<form method="POST" action="index.php?page=' . htmlspecialchars($page, ENT_QUOTES, 'UTF-8') . '" class="friend-action-form">'
            . csrf_input()
            . '<input type="hidden" name="user_id" value="' . (int)$otherUserId . '">'
            . '<button type="submit" class="' . htmlspecialchars(trim($btnClass), ENT_QUOTES, 'UTF-8') . '">'
            . '<i class="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '"></i> ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            . '</button></form>';
    }
}
