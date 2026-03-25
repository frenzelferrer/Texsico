<?php
require_once __DIR__ . '/../../config/database.php';

class UserModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, username, full_name, bio, profile_image, cover_photo, created_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $username, string $email, string $password, string $fullName): int {
        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 8]);
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$username, $email, $hashed, $fullName]);
        return (int) $this->db->lastInsertId();
    }

    public function updateProfile(int $id, string $fullName, string $bio, ?string $profileImage, ?string $coverPhoto = null): bool {
        $fields = ['full_name = ?', 'bio = ?'];
        $values = [$fullName, $bio];

        if ($profileImage !== null) {
            $fields[] = 'profile_image = ?';
            $values[] = $profileImage;
        }

        if ($coverPhoto !== null) {
            $fields[] = 'cover_photo = ?';
            $values[] = $coverPhoto;
        }

        $values[] = $id;
        $stmt = $this->db->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
        return $stmt->execute($values);
    }

    public function searchUsers(string $query, int $excludeId): array {
        $like = "%$query%";
        $stmt = $this->db->prepare(
            "SELECT id, username, full_name, profile_image FROM users 
             WHERE id != ? AND (username LIKE ? OR full_name LIKE ?) 
             ORDER BY full_name ASC LIMIT 20"
        );
        $stmt->execute([$excludeId, $like, $like]);
        return $stmt->fetchAll();
    }

    public function getAllExcept(int $excludeId, ?int $limit = null): array {
        $sql = "SELECT id, username, full_name, profile_image FROM users WHERE id != ? ORDER BY full_name ASC";
        if ($limit !== null && $limit > 0) {
            $sql .= ' LIMIT ' . (int)$limit;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$excludeId]);
        return $stmt->fetchAll();
    }
}
