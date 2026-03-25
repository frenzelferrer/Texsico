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

    public function getAllPosts(int $userId): array {
        $stmt = $this->db->prepare($this->baseSelect() . " ORDER BY p.created_at DESC, p.id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getPostsByUser(int $profileUserId, int $currentUserId): array {
        $stmt = $this->db->prepare($this->baseSelect() . " WHERE p.user_id = ? ORDER BY p.created_at DESC, p.id DESC");
        $stmt->execute([$currentUserId, $profileUserId]);
        return $stmt->fetchAll();
    }

    public function getPostById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM posts WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $userId, string $content, ?string $image): int {
        $stmt = $this->db->prepare(
            "INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)"
        );
        $stmt->execute([$userId, $content, $image]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $userId, string $content): bool {
        $stmt = $this->db->prepare(
            "UPDATE posts SET content=? WHERE id=? AND user_id=?"
        );
        return $stmt->execute([$content, $id, $userId]);
    }

    public function delete(int $id, int $userId): bool {
        $stmt = $this->db->prepare("DELETE FROM posts WHERE id=? AND user_id=?");
        return $stmt->execute([$id, $userId]);
    }

    public function search(string $query, int $userId): array {
        $like = '%' . $query . '%';
        $stmt = $this->db->prepare($this->baseSelect() . " WHERE p.content LIKE ? ORDER BY p.created_at DESC, p.id DESC");
        $stmt->execute([$userId, $like]);
        return $stmt->fetchAll();
    }
}
