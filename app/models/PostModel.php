<?php
require_once __DIR__ . '/../../config/database.php';

class PostModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    private function baseSelect(): string {
        return "SELECT p.*, u.username, u.full_name, u.profile_image,
                COALESCE(ls.like_count, 0) AS like_count,
                CASE WHEN ul.user_id IS NULL THEN 0 ELSE 1 END AS user_liked,
                COALESCE(cs.comment_count, 0) AS comment_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN (
                SELECT post_id, COUNT(*) AS like_count
                FROM likes
                GROUP BY post_id
            ) ls ON ls.post_id = p.id
            LEFT JOIN likes ul ON ul.post_id = p.id AND ul.user_id = ?
            LEFT JOIN (
                SELECT post_id, COUNT(*) AS comment_count
                FROM comments
                GROUP BY post_id
            ) cs ON cs.post_id = p.id";
    }

    private function visibilityCondition(string $ownerColumn = 'p.user_id'): string {
        return "($ownerColumn = ? OR EXISTS (
            SELECT 1
            FROM friendships f
            WHERE f.status = 'accepted'
              AND ((f.user_one_id = ? AND f.user_two_id = $ownerColumn)
                   OR (f.user_two_id = ? AND f.user_one_id = $ownerColumn))
        ))";
    }

    private function visibilityParams(int $viewerId): array {
        return [$viewerId, $viewerId, $viewerId];
    }

    public function getAllPosts(int $userId): array {
        $sql = $this->baseSelect() . ' ORDER BY p.created_at DESC, p.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getPostsByUser(int $profileUserId, int $currentUserId): array {
        if (!$this->canViewAuthorPosts($currentUserId, $profileUserId)) {
            return [];
        }
        $stmt = $this->db->prepare($this->baseSelect() . ' WHERE p.user_id = ? ORDER BY p.created_at DESC, p.id DESC');
        $stmt->execute([$currentUserId, $profileUserId]);
        return $stmt->fetchAll();
    }

    public function getPostById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function canUserAccessPost(int $postId, int $viewerId): bool {
        $stmt = $this->db->prepare('SELECT 1 FROM posts WHERE id = ? LIMIT 1');
        $stmt->execute([$postId]);
        return (bool)$stmt->fetchColumn();
    }

    public function canViewAuthorPosts(int $viewerId, int $authorId): bool {
        if ($viewerId === $authorId) {
            return true;
        }
        $stmt = $this->db->prepare(
            "SELECT 1
             FROM friendships f
             WHERE f.status = 'accepted'
               AND ((f.user_one_id = ? AND f.user_two_id = ?) OR (f.user_two_id = ? AND f.user_one_id = ?))
             LIMIT 1"
        );
        $stmt->execute([$viewerId, $authorId, $viewerId, $authorId]);
        return (bool)$stmt->fetchColumn();
    }

    public function create(int $userId, string $content, ?string $image): int {
        $stmt = $this->db->prepare('INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $content, $image]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $userId, string $content): bool {
        $stmt = $this->db->prepare('UPDATE posts SET content = ? WHERE id = ? AND user_id = ?');
        return $stmt->execute([$content, $id, $userId]);
    }

    public function delete(int $id, int $userId): bool {
        $stmt = $this->db->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
        return $stmt->execute([$id, $userId]);
    }

    public function search(string $query, int $userId): array {
        $like = '%' . $query . '%';
        $sql = $this->baseSelect() . ' WHERE p.content LIKE ? ORDER BY p.created_at DESC, p.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $like]);
        return $stmt->fetchAll();
    }
}
