<?php
define('DB_HOST', 'sql309.infinityfree.com');
define('DB_NAME', 'if0_41474470_texsico_db');
define('DB_USER', 'if0_41474470');
define('DB_PASS', 'blPxcMZGqt');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static ?self $instance = null;
    private PDO $pdo;

    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
    $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $this->pdo->exec("SET time_zone = '+08:00'");
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
}
