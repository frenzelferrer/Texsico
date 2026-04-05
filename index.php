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
date_default_timezone_set('Asia/Manila');
session_start();

define('BASE_PATH', __DIR__);
$hostHeader = preg_replace('/[^A-Za-z0-9.:-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($hostHeader ?: 'localhost') . '/');

require_once BASE_PATH . '/app/helpers/AppHelper.php';
app_boot_optimizations();
apply_security_headers(true);
reject_cross_origin_preflight();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return $_SESSION['csrf_token'] ?? '';
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('verify_csrf_request')) {
    function verify_csrf_request(): void
    {
        ensure_same_origin_request();
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!$token || !hash_equals(csrf_token(), $token)) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }
    }
}


if (!function_exists('default_avatar_data_uri')) {
    function default_avatar_data_uri(string $name, int $size = 128): string
    {
        $name = trim($name) !== '' ? trim($name) : 'Texsico User';
        $parts = preg_split('/\s+/', $name) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(app_substr($part, 0, 1));
        }
        if ($initials === '') {
            $initials = strtoupper(app_substr($name, 0, 2));
        }
        $palette = [
            ['#53d4ff', '#8f88ff'],
            ['#ff7aa8', '#8f88ff'],
            ['#53d4ff', '#ffb86c'],
            ['#6ee7b7', '#53d4ff'],
            ['#f472b6', '#fb7185'],
            ['#60a5fa', '#a78bfa'],
        ];
        $pair = $palette[abs(crc32(app_strtolower($name))) % count($palette)];
        $fontSize = max(22, (int) floor($size * 0.34));
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '">'
            . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
            . '<stop stop-color="' . $pair[0] . '" offset="0%"/><stop stop-color="' . $pair[1] . '" offset="100%"/>'
            . '</linearGradient></defs>'
            . '<rect width="' . $size . '" height="' . $size . '" rx="' . ($size / 2) . '" fill="url(#g)"/>'
            . '<text x="50%" y="53%" dominant-baseline="middle" text-anchor="middle" '
            . 'font-family="Arial, Helvetica, sans-serif" font-size="' . $fontSize . '" font-weight="700" fill="#ffffff">'
            . htmlspecialchars($initials, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</text></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

if (!function_exists('asset_upload_path')) {
    function asset_upload_path(string $type, string $filename): ?string
    {
        $filename = basename($filename);
        $base = match ($type) {
            'avatar' => __DIR__ . '/assets/uploads/avatars/',
            'post' => __DIR__ . '/assets/uploads/posts/',
            'chat' => __DIR__ . '/assets/uploads/chat/',
            'voice' => __DIR__ . '/assets/uploads/voice/',
            'cover' => __DIR__ . '/assets/uploads/covers/',
            default => null,
        };
        if ($base === null) {
            return null;
        }
        $path = $base . $filename;
        return is_file($path) ? $path : null;
    }
}

if (isset($_GET['asset']) && isset($_GET['f'])) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        exit('Authentication required.');
    }

    $type = (string)$_GET['asset'];
    $filename = (string)$_GET['f'];
    $path = asset_upload_path($type, $filename);
    if ($path) {
        if (in_array($type, ['chat', 'voice'], true)) {
            require_once BASE_PATH . '/config/database.php';
            require_once BASE_PATH . '/app/models/MessageModel.php';
            $messageModel = new MessageModel();
            if (!$messageModel->userCanAccessMedia((int)$_SESSION['user_id'], $filename, $type === 'voice' ? 'voice' : 'image')) {
                http_response_code(403);
                exit('Not authorized to access this media.');
            }
            stream_uploaded_asset($path, 0, true);
        }

        $ttl = in_array($type, ['avatar', 'cover'], true) ? 2592000 : 604800;
        stream_uploaded_asset($path, $ttl, true);
    }
    http_response_code(404);
    exit;
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/controllers/AuthController.php';
require_once BASE_PATH . '/app/controllers/PostController.php';
require_once BASE_PATH . '/app/controllers/CommentController.php';
require_once BASE_PATH . '/app/controllers/ProfileController.php';
require_once BASE_PATH . '/app/controllers/ChatController.php';
require_once BASE_PATH . '/app/controllers/FriendshipController.php';
require_once BASE_PATH . '/app/controllers/NotificationController.php';

$page = $_GET['page'] ?? 'login';

$authCtrl = new AuthController();
$postCtrl = new PostController();
$commentCtrl = new CommentController();
$profileCtrl = new ProfileController();
$chatCtrl = new ChatController();
$friendshipCtrl = new FriendshipController();
$notificationCtrl = new NotificationController();

switch ($page) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $authCtrl->login();
        else $authCtrl->showLogin();
        break;

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $authCtrl->register();
        else $authCtrl->showRegister();
        break;

    case 'forgot-password':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $authCtrl->sendPasswordReset();
        else $authCtrl->showForgotPassword();
        break;

    case 'forgot-password.send':
        $authCtrl->sendPasswordReset();
        break;

    case 'reset-password':
        $authCtrl->showResetPassword();
        break;

    case 'reset-password.save':
        $authCtrl->resetPassword();
        break;

    case 'logout':
        $authCtrl->logout();
        break;

    case 'feed':
        $postCtrl->showFeed();
        break;

    case 'post.create':
        $postCtrl->createPost();
        break;

    case 'post.update':
        $postCtrl->updatePost();
        break;

    case 'post.delete':
        $postCtrl->deletePost();
        break;

    case 'post.like':
        $postCtrl->toggleLike();
        break;

    case 'comment.add':
        $commentCtrl->addComment();
        break;

    case 'comment.delete':
        $commentCtrl->deleteComment();
        break;

    case 'comment.update':
        $commentCtrl->updateComment();
        break;

    case 'profile':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $profileCtrl->updateProfile();
        else $profileCtrl->showProfile();
        break;

    case 'search':
        $profileCtrl->searchUsers();
        break;

    case 'chat':
        $chatCtrl->showChat();
        break;

    case 'chat.send':
        $chatCtrl->sendMessage();
        break;

    case 'chat.poll':
        $chatCtrl->pollMessages();
        break;

    case 'friend.request':
        $friendshipCtrl->request();
        break;

    case 'friend.accept':
        $friendshipCtrl->accept();
        break;

    case 'friend.remove':
        $friendshipCtrl->remove();
        break;

    case 'notifications.read':
        $notificationCtrl->markAllRead();
        break;

    default:
        header('Location: index.php?page=login');
        exit;
}
