<?php

if (!function_exists('app_boot_optimizations')) {
    function app_boot_optimizations(): void {
        if (!headers_sent() && extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
            @ini_set('zlib.output_compression', '1');
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
    function stream_uploaded_asset(string $path, int $maxAge = 604800): void {
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
        header('Cache-Control: public, max-age=' . max(3600, $maxAge) . ', immutable');
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
