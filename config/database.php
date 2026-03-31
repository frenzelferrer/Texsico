<?php
    define('DB_HOST', 'sql202.infinityfree.com');
define('DB_NAME', 'if0_41488179_texsico_db');
define('DB_USER', 'if0_41488179');
define('DB_PASS', '3URbHDaNbNXF');
define('DB_CHARSET', 'utf8mb4');
$credentialsFile = __DIR__ . '/database.credentials.php';
if (is_file($credentialsFile)) {
    require_once $credentialsFile;
}

if (!defined('DB_DSN')) {
    define('DB_DSN', getenv('DB_DSN') ?: ($_SERVER['DB_DSN'] ?? ''));
}
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: ($_SERVER['DB_HOST'] ?? ''));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: ($_SERVER['DB_NAME'] ?? ''));
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: ($_SERVER['DB_USER'] ?? ''));
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') ?: ($_SERVER['DB_PASS'] ?? ''));
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', getenv('DB_CHARSET') ?: ($_SERVER['DB_CHARSET'] ?? 'utf8mb4'));
}

class Database {
    private static ?self $instance = null;
    private PDO $pdo;
    private string $driver;

    private function __construct() {
        $dsn = trim((string) DB_DSN);
        if ($dsn === '') {
            if (trim((string) DB_HOST) === '' || trim((string) DB_NAME) === '' || trim((string) DB_USER) === '') {
                error_log('Database credentials are not configured. Create config/database.credentials.php or set DB_* environment variables.');
                http_response_code(500);
                exit('Database is not configured. See config/database.credentials.example.php.');
            }
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER !== '' ? DB_USER : null, DB_PASS !== '' ? DB_PASS : null, $options);
            $this->driver = (string)$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($this->driver === 'mysql') {
                $this->pdo->exec("SET time_zone = '+08:00'");
            }
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Internal server error.');
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }

    public function getDriver(): string {
        return $this->driver;
    }
}
