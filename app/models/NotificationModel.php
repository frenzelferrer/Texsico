<?php
require_once __DIR__ . '/../../config/database.php';

class NotificationModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(int $userId, int $actorId, string $type, ?int $resourceId, string $message): bool {
        if ($userId <= 0 || $actorId <= 0 || $userId === $actorId) {
            return false;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO notifications (user_id, actor_id, type, resource_id, message, is_read, created_at)
             VALUES (?, ?, ?, ?, ?, 0, NOW())"
        );
        return $stmt->execute([$userId, $actorId, $type, $resourceId, $message]);
    }

    public function getRecentForUser(int $userId, int $limit = 8): array {
        $limit = max(1, min(30, $limit));
        $stmt = $this->db->prepare(
            "SELECT n.*, u.username AS actor_username, u.full_name AS actor_name, u.profile_image AS actor_image
             FROM notifications n
             JOIN users u ON u.id = n.actor_id
             WHERE n.user_id = ?
             ORDER BY n.created_at DESC, n.id DESC
             LIMIT $limit"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getUnreadCount(int $userId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function markAllRead(int $userId): bool {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        return $stmt->execute([$userId]);
    }
}
