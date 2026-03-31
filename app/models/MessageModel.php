<?php
require_once __DIR__ . '/../../config/database.php';

class MessageModel {
    private PDO $db;
    private ?bool $hasReplyColumn = null;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    private function hasReplyColumn(): bool {
        if ($this->hasReplyColumn !== null) {
            return $this->hasReplyColumn;
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM messages LIKE 'reply_to_message_id'");
            $this->hasReplyColumn = (bool)$stmt->fetch();
        } catch (Throwable $e) {
            $this->hasReplyColumn = false;
        }

        return $this->hasReplyColumn;
    }

    private function replySelectColumns(): string {
        if ($this->hasReplyColumn()) {
            return "rm.id AS reply_id,
                    rm.sender_id AS reply_sender_id,
                    ru.full_name AS reply_sender_name,
                    rm.message_type AS reply_message_type,
                    rm.message AS reply_message,
                    rm.media_file AS reply_media_file,
                    rm.media_duration AS reply_media_duration";
        }

        return "NULL AS reply_id,
                    NULL AS reply_sender_id,
                    NULL AS reply_sender_name,
                    NULL AS reply_message_type,
                    NULL AS reply_message,
                    NULL AS reply_media_file,
                    NULL AS reply_media_duration";
    }

    private function replyJoinSql(): string {
        if (!$this->hasReplyColumn()) {
            return '';
        }

        return "
             LEFT JOIN messages rm ON m.reply_to_message_id = rm.id
             LEFT JOIN users ru ON rm.sender_id = ru.id";
    }

    private function conversationSelectColumns(): string {
        return "m.*,
                    su.username AS sender_username,
                    su.full_name AS sender_name,
                    su.profile_image AS sender_image,
                    " . $this->replySelectColumns();
    }

    private function hydrateMessages(array $rows): array {
        foreach ($rows as &$row) {
            $row['time_formatted'] = format_chat_time((string)($row['created_at'] ?? ''));
            if (empty($row['reply_id'])) {
                $row['reply_id'] = null;
            }
        }
        unset($row);
        return $rows;
    }

    public function getConversation(int $userId, int $otherId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM (
                SELECT " . $this->conversationSelectColumns() . "
                FROM messages m
                JOIN users su ON m.sender_id = su.id" . $this->replyJoinSql() . "
                WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.id DESC
                LIMIT 120
            ) recent
            ORDER BY recent.id ASC"
        );
        $stmt->execute([$userId, $otherId, $otherId, $userId]);
        return $this->hydrateMessages($stmt->fetchAll());
    }

    public function send(int $senderId, int $receiverId, string $message, ?int $replyToMessageId = null): int {
        if ($this->hasReplyColumn()) {
            $stmt = $this->db->prepare(
                "INSERT INTO messages (sender_id, receiver_id, message, message_type, reply_to_message_id) VALUES (?, ?, ?, 'text', ?)"
            );
            $stmt->execute([$senderId, $receiverId, $message, $replyToMessageId]);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO messages (sender_id, receiver_id, message, message_type) VALUES (?, ?, ?, 'text')"
            );
            $stmt->execute([$senderId, $receiverId, $message]);
        }
        return (int) $this->db->lastInsertId();
    }

    public function sendMedia(int $senderId, int $receiverId, string $messageType, string $mediaFile, ?int $duration = null, ?int $replyToMessageId = null): int {
        if ($this->hasReplyColumn()) {
            $stmt = $this->db->prepare(
                "INSERT INTO messages (sender_id, receiver_id, message, message_type, media_file, media_duration, reply_to_message_id)
                 VALUES (?, ?, '', ?, ?, ?, ?)"
            );
            $stmt->execute([$senderId, $receiverId, $messageType, $mediaFile, $duration, $replyToMessageId]);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO messages (sender_id, receiver_id, message, message_type, media_file, media_duration)
                 VALUES (?, ?, '', ?, ?, ?)"
            );
            $stmt->execute([$senderId, $receiverId, $messageType, $mediaFile, $duration]);
        }
        return (int)$this->db->lastInsertId();
    }

    public function markRead(int $senderId, int $receiverId): void {
        $stmt = $this->db->prepare(
            "UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0"
        );
        $stmt->execute([$senderId, $receiverId]);
    }

    public function getLastReadMessageId(int $senderId, int $receiverId): int {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(id), 0) FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_read = 1"
        );
        $stmt->execute([$senderId, $receiverId]);
        return (int)$stmt->fetchColumn();
    }

    public function getUnreadCount(int $userId): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0"
        );
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function getConversationList(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.username, u.full_name, u.profile_image,
                    CASE
                        WHEN lm.message_type = 'image' THEN '[Image]'
                        WHEN lm.message_type = 'voice' THEN '[Voice message]'
                        ELSE lm.message
                    END AS last_message,
                    lm.created_at AS last_created_at,
                    COALESCE(unread_counts.unread, 0) AS unread
             FROM (
                SELECT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS partner_id,
                       MAX(id) AS last_message_id
                FROM messages
                WHERE sender_id = ? OR receiver_id = ?
                GROUP BY CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END
             ) conv
             JOIN messages lm ON lm.id = conv.last_message_id
             JOIN users u ON u.id = conv.partner_id
             JOIN friendships f ON f.status = 'accepted'
                AND ((f.user_one_id = ? AND f.user_two_id = u.id) OR (f.user_two_id = ? AND f.user_one_id = u.id))
             LEFT JOIN (
                SELECT sender_id, COUNT(*) AS unread
                FROM messages
                WHERE receiver_id = ? AND is_read = 0
                GROUP BY sender_id
             ) unread_counts ON unread_counts.sender_id = u.id
             ORDER BY lm.created_at DESC, lm.id DESC"
        );
        $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['last_time'] = format_chat_time((string)$row['last_created_at']);
        }
        unset($row);
        return $rows;
    }

    public function getNewMessages(int $userId, int $otherId, int $lastId): array {
        $stmt = $this->db->prepare(
            "SELECT " . $this->conversationSelectColumns() . "
             FROM messages m
             JOIN users su ON m.sender_id = su.id" . $this->replyJoinSql() . "
             WHERE m.id > ?
               AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
             ORDER BY m.created_at ASC, m.id ASC"
        );
        $stmt->execute([$lastId, $userId, $otherId, $otherId, $userId]);
        return $this->hydrateMessages($stmt->fetchAll());
    }

    public function getSharedImages(int $userId, int $otherId, int $limit = 6): array {
        $limit = max(1, min(12, $limit));

        $stmt = $this->db->prepare(
            "SELECT id, media_file, created_at
             FROM messages
             WHERE (
                (sender_id = ? AND receiver_id = ?)
                OR
                (sender_id = ? AND receiver_id = ?)
             )
             AND message_type = 'image'
             AND media_file IS NOT NULL
             AND media_file <> ''
             ORDER BY id DESC
             LIMIT $limit"
        );

        $stmt->execute([$userId, $otherId, $otherId, $userId]);
        return $stmt->fetchAll();
    }

    public function getConversationStats(int $userId, int $otherId): array {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total_messages,
                SUM(CASE WHEN message_type = 'image' THEN 1 ELSE 0 END) AS total_photos,
                SUM(CASE WHEN message_type = 'voice' THEN 1 ELSE 0 END) AS total_voice
             FROM messages
             WHERE (
                (sender_id = ? AND receiver_id = ?)
                OR
                (sender_id = ? AND receiver_id = ?)
             )"
        );

        $stmt->execute([$userId, $otherId, $otherId, $userId]);
        $row = $stmt->fetch();

        return [
            'total_messages' => (int)($row['total_messages'] ?? 0),
            'total_photos'   => (int)($row['total_photos'] ?? 0),
            'total_voice'    => (int)($row['total_voice'] ?? 0),
        ];
    }

    public function getMessageById(int $messageId): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM messages WHERE id = ?"
        );
        $stmt->execute([$messageId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getMessageByIdForUser(int $messageId, int $userId): ?array {
        $sql = "SELECT m.*,
                    " . $this->replySelectColumns() . "
             FROM messages m";

        if ($this->hasReplyColumn()) {
            $sql .= "
             LEFT JOIN messages rm ON m.reply_to_message_id = rm.id
             LEFT JOIN users ru ON rm.sender_id = ru.id";
        }

        $sql .= "
             WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?)
             LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$messageId, $userId, $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findConversationMessageForUser(int $userId, int $otherId, int $messageId): ?array {
        $stmt = $this->db->prepare(
            "SELECT m.*,
                    su.full_name AS sender_name
             FROM messages m
             JOIN users su ON m.sender_id = su.id
             WHERE m.id = ?
               AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
             LIMIT 1"
        );
        $stmt->execute([$messageId, $userId, $otherId, $otherId, $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function userCanAccessMedia(int $userId, string $mediaFile, string $messageType): bool {
        $stmt = $this->db->prepare(
            "SELECT 1
             FROM messages m
             JOIN friendships f ON f.status = 'accepted'
                AND ((f.user_one_id = m.sender_id AND f.user_two_id = m.receiver_id)
                     OR (f.user_two_id = m.sender_id AND f.user_one_id = m.receiver_id))
             WHERE m.media_file = ?
               AND m.message_type = ?
               AND (m.sender_id = ? OR m.receiver_id = ?)
             LIMIT 1"
        );
        $stmt->execute([basename($mediaFile), $messageType, $userId, $userId]);
        return (bool)$stmt->fetchColumn();
    }
}
