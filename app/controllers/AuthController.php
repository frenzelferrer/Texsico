<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/PasswordResetModel.php';
require_once __DIR__ . '/../helpers/MailHelper.php';

class AuthController {
    private UserModel $userModel;
    private PasswordResetModel $passwordResetModel;
    private MailHelper $mailHelper;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->passwordResetModel = new PasswordResetModel();
        $this->mailHelper = new MailHelper();
    }

    private function redirect(string $page): void {
        header('Location: index.php?page=' . $page);
        exit;
    }

    private function checkLoginRateLimit(): void {
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $last = $_SESSION['login_last_attempt'] ?? 0;
        if ($attempts >= 5 && (time() - $last) < 300) {
            $_SESSION['error'] = 'Too many login attempts. Please wait a few minutes and try again.';
            $this->redirect('login');
        }
    }

    private function recordFailedLogin(): void {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['login_last_attempt'] = time();
    }

    private function clearFailedLogins(): void {
        unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt']);
    }

    private function checkRegisterRateLimit(): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'reg_attempts_' . md5($ip);

        $attempts = $_SESSION[$key] ?? 0;
        $window = $_SESSION[$key . '_time'] ?? 0;

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

    private function genericResetMessage(): string {
        return 'If that email is registered, a password reset link has been sent.';
    }

    private function validateStrongPassword(string $password, string $confirm): array {
        $errors = [];
        if (strlen($password) < 8 || strlen($password) > 255) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must include both lowercase and uppercase letters.';
        }
        if (!preg_match('/\d/', $password) && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must include at least one number or symbol.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }
        return $errors;
    }

    private function normalizeEmail(string $email): string {
        return app_strtolower(app_normalize_single_line($email, 120));
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

        $identifier = app_normalize_single_line((string)($_POST['identifier'] ?? ''), 120);
        $password = $_POST['password'] ?? '';

        if ($identifier === '' || $password === '') {
            $_SESSION['error'] = 'Please fill in all fields.';
            $this->redirect('login');
        }

        if (app_strlen($identifier) > 120 || app_strlen($password) > 255) {
            $_SESSION['error'] = 'Invalid credentials. Please try again.';
            $this->redirect('login');
        }

        $user = $this->userModel->findByUsername($identifier)
             ?? $this->userModel->findByEmail($identifier);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->recordFailedLogin();
            $_SESSION['error'] = 'Invalid credentials. Please try again.';
            $this->redirect('login');
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

    public function register(): void {
        verify_csrf_request();
        $this->checkRegisterRateLimit();

        $username = app_normalize_single_line((string)($_POST['username'] ?? ''), 20);
        $email = $this->normalizeEmail((string)($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $fullName = app_normalize_single_line((string)($_POST['full_name'] ?? ''), 80);

        $errors = [];
        if ($username === '' || $email === '' || $password === '' || $fullName === '') {
            $errors[] = 'All fields are required.';
        }
        if (app_strlen($username) < 3 || app_strlen($username) > 20) {
            $errors[] = 'Username must be between 3 and 20 characters.';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || app_strlen($email) > 120) {
            $errors[] = 'Invalid email address.';
        }
        if (app_strlen($fullName) < 1 || app_strlen($fullName) > 80) {
            $errors[] = 'Full name must be between 1 and 80 characters.';
        }
        $errors = array_merge($errors, $this->validateStrongPassword($password, $confirm));

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
            $this->redirect('register');
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

    public function showForgotPassword(): void {
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php?page=feed');
            exit;
        }
        require __DIR__ . '/../views/auth/forgot_password.php';
    }

    public function sendPasswordReset(): void {
        verify_csrf_request();

        $email = $this->normalizeEmail((string)($_POST['email'] ?? ''));
        $ipKey = 'pw_reset_ip_' . md5((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $emailKey = 'pw_reset_email_' . md5($email);
        $cooldownSeconds = 60;

        if (!app_rate_limit($ipKey, 5, 900) || ($email !== '' && !app_rate_limit($emailKey, 3, 900))) {
            $_SESSION['status'] = $this->genericResetMessage();
            $this->redirect('forgot-password');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['status'] = $this->genericResetMessage();
            $this->redirect('forgot-password');
        }

        $user = $this->userModel->findByEmail($email);
        if ($user) {
            $recentReset = $this->passwordResetModel->findRecentActiveByUser((int)$user['id'], $cooldownSeconds);
            if (!$recentReset) {
                $rawToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
                $tokenHash = hash('sha256', $rawToken);
                $expiresAt = date('Y-m-d H:i:s', time() + (20 * 60));
                $ip = app_normalize_single_line((string)($_SERVER['REMOTE_ADDR'] ?? ''), 64);
                $ua = app_normalize_single_line((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 255);

                $this->passwordResetModel->create((int)$user['id'], $user['email'], $tokenHash, $expiresAt, $ip, $ua);
                $resetLink = BASE_URL . 'index.php?page=reset-password&token=' . rawurlencode($rawToken);
                $this->mailHelper->sendPasswordReset($user['email'], (string)$user['full_name'], $resetLink, 20);
            }
        }

        $_SESSION['status'] = $this->genericResetMessage();
        $this->redirect('forgot-password');
    }

    public function showResetPassword(): void {
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php?page=feed');
            exit;
        }

        $token = app_normalize_single_line((string)($_GET['token'] ?? ''), 255);
        $tokenHash = $token !== '' ? hash('sha256', $token) : '';
        $reset = $tokenHash !== '' ? $this->passwordResetModel->findActiveByHash($tokenHash) : null;
        $tokenIsValid = $reset !== null;
        require __DIR__ . '/../views/auth/reset_password.php';
    }

    public function resetPassword(): void {
        verify_csrf_request();

        $token = app_normalize_single_line((string)($_POST['token'] ?? ''), 255);
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $tokenHash = $token !== '' ? hash('sha256', $token) : '';
        $reset = $tokenHash !== '' ? $this->passwordResetModel->findActiveByHash($tokenHash) : null;

        if (!$reset) {
            $_SESSION['error'] = 'That reset link is invalid or has expired.';
            $this->redirect('forgot-password');
        }

        $errors = $this->validateStrongPassword($password, $confirm);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['pw_reset_token'] = $token;
            header('Location: index.php?page=reset-password&token=' . rawurlencode($token));
            exit;
        }

        $this->userModel->updatePasswordById((int)$reset['user_id'], $password);
        $this->passwordResetModel->markUsed((int)$reset['id']);
        $this->passwordResetModel->invalidateActiveTokensForUser((int)$reset['user_id']);

        $_SESSION['status'] = 'Your password has been reset. You can sign in now.';
        $this->redirect('login');
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
