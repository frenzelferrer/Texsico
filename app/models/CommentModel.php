<?php
require_once __DIR__ . '/../../config/database.php';

class CommentModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByPost(int $postId): array {
        $stmt = $this->db->prepare(
            "SELECT c.*, u.username, u.full_name, u.profile_image
             FROM comments c
             JOIN users u ON c.user_id = u.id
             WHERE c.post_id = ?
             ORDER BY c.created_at ASC, c.id ASC"
        );
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }

    public function getByPostIds(array $postIds): array {
        $postIds = array_values(array_unique(array_map('intval', $postIds)));
        if ($postIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT c.*, u.username, u.full_name, u.profile_image
             FROM comments c
             JOIN users u ON c.user_id = u.id
             WHERE c.post_id IN ($placeholders)
             ORDER BY c.created_at ASC, c.id ASC"
        );
        $stmt->execute($postIds);
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int)$row['post_id']][] = $row;
        }
        return $grouped;
    }

    public function create(int $postId, int $userId, string $content): int {
        $stmt = $this->db->prepare(
            "INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)"
        );
        $stmt->execute([$postId, $userId, $content]);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT c.*, u.username, u.full_name, u.profile_image
             FROM comments c
             JOIN users u ON c.user_id = u.id
             WHERE c.id = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function update(int $id, int $userId, string $content): bool {
        $stmt = $this->db->prepare(
            "UPDATE comments SET content = ? WHERE id = ? AND user_id = ?"
        );
        $stmt->execute([$content, $id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id, int $userId): bool {
        $stmt = $this->db->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }
}
