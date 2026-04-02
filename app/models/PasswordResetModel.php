<?php
require_once __DIR__ . '/../../config/database.php';

class PasswordResetModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function invalidateActiveTokensForUser(int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE password_resets SET used_at = CURRENT_TIMESTAMP WHERE user_id = ? AND used_at IS NULL');
        $stmt->execute([$userId]);
    }

    public function create(int $userId, string $email, string $tokenHash, string $expiresAt, string $requestedIp, string $userAgent): int
    {
        $this->invalidateActiveTokensForUser($userId);
        $stmt = $this->db->prepare(
            'INSERT INTO password_resets (user_id, email, token_hash, expires_at, requested_ip, user_agent) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $email, $tokenHash, $expiresAt, $requestedIp, $userAgent]);
        return (int)$this->db->lastInsertId();
    }

    public function findRecentActiveByUser(int $userId, int $cooldownSeconds): ?array
    {
        $seconds = max(1, (int)$cooldownSeconds);
        $stmt = $this->db->prepare(
            'SELECT * FROM password_resets WHERE user_id = ? AND used_at IS NULL AND expires_at > CURRENT_TIMESTAMP AND created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL ' . $seconds . ' SECOND) ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function findActiveByHash(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > CURRENT_TIMESTAMP ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        return $stmt->fetch() ?: null;
    }

    public function markUsed(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE password_resets SET used_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function purgeExpired(): void
    {
        $stmt = $this->db->prepare('DELETE FROM password_resets WHERE expires_at <= CURRENT_TIMESTAMP OR used_at IS NOT NULL');
        $stmt->execute();
    }
}
