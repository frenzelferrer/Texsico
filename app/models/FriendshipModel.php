<?php
require_once __DIR__ . '/../../config/database.php';

class FriendshipModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    private function normalizePair(int $a, int $b): array {
        return $a < $b ? [$a, $b] : [$b, $a];
    }

    public function getFriendship(int $viewerId, int $otherId): ?array {
        if ($viewerId <= 0 || $otherId <= 0 || $viewerId === $otherId) {
            return null;
        }

        [$one, $two] = $this->normalizePair($viewerId, $otherId);
        $stmt = $this->db->prepare(
            "SELECT * FROM friendships WHERE user_one_id = ? AND user_two_id = ? LIMIT 1"
        );
        $stmt->execute([$one, $two]);
        return $stmt->fetch() ?: null;
    }

    public function getState(int $viewerId, int $otherId): string {
        if ($viewerId === $otherId) {
            return 'self';
        }

        $row = $this->getFriendship($viewerId, $otherId);
        if (!$row) {
            return 'none';
        }

        if (($row['status'] ?? '') === 'accepted') {
            return 'accepted';
        }

        return ((int)($row['requested_by'] ?? 0) === $viewerId) ? 'outgoing_pending' : 'incoming_pending';
    }

    public function areFriends(int $viewerId, int $otherId): bool {
        $row = $this->getFriendship($viewerId, $otherId);
        return (bool)$row && (($row['status'] ?? '') === 'accepted');
    }

    public function requestFriend(int $requesterId, int $targetId): bool {
        if ($requesterId <= 0 || $targetId <= 0 || $requesterId === $targetId) {
            return false;
        }

        $existing = $this->getFriendship($requesterId, $targetId);
        if ($existing) {
            if (($existing['status'] ?? '') === 'accepted') {
                return true;
            }

            if ((int)($existing['requested_by'] ?? 0) === $targetId) {
                return $this->acceptFriend($requesterId, $targetId);
            }

            return true;
        }

        [$one, $two] = $this->normalizePair($requesterId, $targetId);
        $stmt = $this->db->prepare(
            "INSERT INTO friendships (user_one_id, user_two_id, requested_by, status, created_at, updated_at)
             VALUES (?, ?, ?, 'pending', NOW(), NOW())"
        );
        return $stmt->execute([$one, $two, $requesterId]);
    }

    public function acceptFriend(int $currentUserId, int $otherUserId): bool {
        $existing = $this->getFriendship($currentUserId, $otherUserId);
        if (!$existing || ($existing['status'] ?? '') !== 'pending' || (int)($existing['requested_by'] ?? 0) === $currentUserId) {
            return false;
        }

        [$one, $two] = $this->normalizePair($currentUserId, $otherUserId);
        $stmt = $this->db->prepare(
            "UPDATE friendships SET status = 'accepted', updated_at = NOW() WHERE user_one_id = ? AND user_two_id = ?"
        );
        return $stmt->execute([$one, $two]);
    }

    public function removeFriend(int $viewerId, int $otherId): bool {
        if ($viewerId <= 0 || $otherId <= 0 || $viewerId === $otherId) {
            return false;
        }

        [$one, $two] = $this->normalizePair($viewerId, $otherId);
        $stmt = $this->db->prepare("DELETE FROM friendships WHERE user_one_id = ? AND user_two_id = ?");
        return $stmt->execute([$one, $two]);
    }

    public function getFriends(int $userId, ?int $limit = null): array {
        $sql = "SELECT u.id, u.username, u.full_name, u.profile_image, u.bio
                FROM friendships f
                JOIN users u ON u.id = CASE
                    WHEN f.user_one_id = ? THEN f.user_two_id
                    ELSE f.user_one_id
                END
                WHERE f.status = 'accepted' AND (f.user_one_id = ? OR f.user_two_id = ?)
                ORDER BY u.full_name ASC";

        if ($limit !== null && $limit > 0) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    public function getFriendCount(int $userId): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM friendships WHERE status = 'accepted' AND (user_one_id = ? OR user_two_id = ?)"
        );
        $stmt->execute([$userId, $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function getPendingIncomingRequests(int $userId, int $limit = 8): array {
        $limit = max(1, min(20, $limit));
        $stmt = $this->db->prepare(
            "SELECT u.id, u.username, u.full_name, u.profile_image, f.created_at
             FROM friendships f
             JOIN users u ON u.id = f.requested_by
             WHERE f.status = 'pending'
               AND f.requested_by <> ?
               AND (f.user_one_id = ? OR f.user_two_id = ?)
             ORDER BY f.created_at DESC
             LIMIT $limit"
        );
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    public function getPendingIncomingCount(int $userId): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM friendships f
             WHERE f.status = 'pending'
               AND f.requested_by <> ?
               AND (f.user_one_id = ? OR f.user_two_id = ?)"
        );
        $stmt->execute([$userId, $userId, $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function getStateMap(int $viewerId, array $otherUserIds): array {
        $map = [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $otherUserIds), static fn(int $id): bool => $id > 0 && $id !== $viewerId)));
        if (empty($ids)) {
            return $map;
        }

        $clauses = [];
        $params = [];
        foreach ($ids as $id) {
            [$one, $two] = $this->normalizePair($viewerId, $id);
            $clauses[] = '(user_one_id = ? AND user_two_id = ?)';
            $params[] = $one;
            $params[] = $two;
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM friendships WHERE ' . implode(' OR ', $clauses)
        );
        $stmt->execute($params);

        foreach ($stmt->fetchAll() as $row) {
            $otherId = ((int)$row['user_one_id'] === $viewerId) ? (int)$row['user_two_id'] : (int)$row['user_one_id'];
            if (($row['status'] ?? '') === 'accepted') {
                $map[$otherId] = 'accepted';
            } else {
                $map[$otherId] = ((int)($row['requested_by'] ?? 0) === $viewerId) ? 'outgoing_pending' : 'incoming_pending';
            }
        }

        foreach ($ids as $id) {
            $map[$id] = $map[$id] ?? 'none';
        }

        return $map;
    }
}
