<?php
require_once __DIR__ . '/../models/UserModel.php';

class AuthController {
    private UserModel $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    private function checkLoginRateLimit(): void {
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $last = $_SESSION['login_last_attempt'] ?? 0;
        if ($attempts >= 5 && (time() - $last) < 300) {
            $_SESSION['error'] = 'Too many login attempts. Please wait a few minutes and try again.';
            header('Location: index.php?page=login');
            exit;
        }
    }

    private function recordFailedLogin(): void {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['login_last_attempt'] = time();
    }

    private function clearFailedLogins(): void {
        unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt']);
    }

    public function showLogin(): void {
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php?page=feed');
            exit;
        }
        require __DIR__ . '/../views/auth/login.php';
    }

    public function login(): void {
        verify_csrf_request();
        $this->checkLoginRateLimit();

        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($identifier === '' || $password === '') {
            $_SESSION['error'] = 'Please fill in all fields.';
            header('Location: index.php?page=login');
            exit;
        }

        if (mb_strlen($identifier) > 120 || mb_strlen($password) > 255) {
            $_SESSION['error'] = 'Invalid credentials. Please try again.';
            header('Location: index.php?page=login');
            exit;
        }

        $user = $this->userModel->findByUsername($identifier)
             ?? $this->userModel->findByEmail($identifier);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->recordFailedLogin();
            $_SESSION['error'] = 'Invalid credentials. Please try again.';
            header('Location: index.php?page=login');
            exit;
        }

        $this->clearFailedLogins();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['profile_image'] = $user['profile_image'] ?: 'default.png';
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        header('Location: index.php?page=feed');
        exit;
    }

    public function showRegister(): void {
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php?page=feed');
            exit;
        }
        require __DIR__ . '/../views/auth/register.php';
    }
private function checkRegisterRateLimit(): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'reg_attempts_' . md5($ip);

    $attempts = $_SESSION[$key] ?? 0;
    $window   = $_SESSION[$key . '_time'] ?? 0;

    if (time() - $window > 3600) {
        $attempts = 0;
        $_SESSION[$key . '_time'] = time();
    }

    if ($attempts >= 5) {
        http_response_code(429);
        exit('Too many registration attempts. Try again in an hour.');
    }

    $_SESSION[$key] = $attempts + 1;
}
    public function register(): void {
        verify_csrf_request();
        $this->checkRegisterRateLimit();

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');

        $errors = [];
        if ($username === '' || $email === '' || $password === '' || $fullName === '') {
            $errors[] = 'All fields are required.';
        }
        if (mb_strlen($username) < 3 || mb_strlen($username) > 30) {
            $errors[] = 'Username must be between 3 and 30 characters.';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
            $errors[] = 'Invalid email address.';
        }
        if (mb_strlen($fullName) < 1 || mb_strlen($fullName) > 80) {
            $errors[] = 'Full name must be between 1 and 80 characters.';
        }
        if (strlen($password) < 8 || strlen($password) > 255) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            if ($this->userModel->findByUsername($username) || $this->userModel->findByEmail($email)) {
                $errors[] = 'That username or email is unavailable.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = [
                'username' => $username,
                'email' => $email,
                'full_name' => $fullName,
            ];
            header('Location: index.php?page=register');
            exit;
        }

        $newId = $this->userModel->create($username, $email, $password, $fullName);
        session_regenerate_id(true);
        $_SESSION['user_id'] = $newId;
        $_SESSION['username'] = $username;
        $_SESSION['full_name'] = $fullName;
        $_SESSION['profile_image'] = 'default.png';
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        header('Location: index.php?page=feed');
        exit;
    }

    public function logout(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=feed');
            exit;
        }
        verify_csrf_request();

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        header('Location: index.php?page=login');
        exit;
    }
}
