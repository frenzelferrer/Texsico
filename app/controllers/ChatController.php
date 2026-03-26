<?php
require_once __DIR__ . '/../models/MessageModel.php';
require_once __DIR__ . '/../models/UserModel.php';

class ChatController {
    private MessageModel $messageModel;
    private UserModel $userModel;

    public function __construct() {
        $this->messageModel = new MessageModel();
        $this->userModel = new UserModel();
    }

    private function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
    }

    private function pollGate(int $userId): bool {
        $lastPollKey = 'last_poll_' . $userId;
        $now = microtime(true);
        $lastPoll = (float)($_SESSION[$lastPollKey] ?? 0);
        if (($now - $lastPoll) < 2.5) {
            return false;
        }
        $_SESSION[$lastPollKey] = $now;
        return true;
    }

    private function uploadVoiceMessage(array $file): ?string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) return null;
        if (!is_uploaded_file($file['tmp_name'])) return null;

        $mime = uploaded_file_mime($file['tmp_name']);
        $allowed = [
            'audio/webm' => 'webm',
            'audio/ogg' => 'ogg',
            'audio/mpeg' => 'mp3',
            'audio/mp4' => 'm4a',
            'audio/wav' => 'wav',
            'audio/x-wav' => 'wav',
        ];

        if ($mime === null || !isset($allowed[$mime])) return null;

        $dir = BASE_PATH . '/assets/uploads/voice/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }

        $filename = 'voice_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
        $dest = $dir . $filename;

        return move_uploaded_file($file['tmp_name'], $dest) ? $filename : null;
    }

    public function showChat(): void {
        $this->requireAuth();
        $userId = (int)$_SESSION['user_id'];
        $otherId = (int)($_GET['with'] ?? 0);
        $otherUser = null;
        $messages = [];
        $conversations = $this->messageModel->getConversationList($userId);
        $allUsers = $this->userModel->getAllExcept($userId, 120);
        $lastMsgId = 0;
        $sharedImages = [];
        $unreadMsgCount = $this->messageModel->getUnreadCount($userId);
        $chatStats = [
            'total_messages' => 0,
            'total_photos' => 0,
            'total_voice' => 0,
        ];

        if ($otherId > 0) {
            $otherUser = $this->userModel->findById($otherId);
            if ($otherUser) {
                $this->messageModel->markRead($otherId, $userId);
                $messages = $this->messageModel->getConversation($userId, $otherId);
                if ($messages) {
                    $ids = array_map(static fn(array $m): int => (int)$m['id'], $messages);
                    $lastMsgId = max($ids);
                }

                $sharedImages = $this->messageModel->getSharedImages($userId, $otherId, 6);
                $chatStats = $this->messageModel->getConversationStats($userId, $otherId);
            }
        }

        require __DIR__ . '/../views/chat/index.php';
    }

    public function sendMessage(): void {
        $this->requireAuth();
        verify_csrf_request();
        header('Content-Type: application/json');

        $userId = (int)$_SESSION['user_id'];
        if (!app_rate_limit('chat_send_' . $userId, 35, 300)) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'You are sending messages too quickly.']);
            exit;
        }

        $receiverId = (int)($_POST['receiver_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $voiceDuration = isset($_POST['voice_duration']) ? max(0, (int)$_POST['voice_duration']) : null;

        if ($receiverId <= 0 || $receiverId === $userId) {
            echo json_encode(['success' => false]);
            exit;
        }

        $receiver = $this->userModel->findById($receiverId);
        if (!$receiver) {
            echo json_encode(['success' => false]);
            exit;
        }

        if (!empty($_FILES['chat_image']['name'])) {
            $image = optimized_image_upload($_FILES['chat_image'], 'chat', 'chat_' . $userId, [
                'max_width' => 1280,
                'max_height' => 1280,
                'quality' => 72,
                'max_file_size' => 5,
            ]);
            if (!$image) {
                echo json_encode(['success' => false, 'error' => 'Invalid image upload.']);
                exit;
            }
            $id = $this->messageModel->sendMedia($userId, $receiverId, 'image', $image);
            $msg = $this->messageModel->getMessageById($id);
            $time = $msg ? format_chat_time((string)$msg['created_at']) : format_chat_time((new DateTime('now'))->format('Y-m-d H:i:s'));
            echo json_encode([
                'success' => true,
                'id' => $id,
                'message_type' => 'image',
                'media_file' => $image,
                'time' => $time,
                'is_read' => 0
            ]);
            exit;
        }

        if (!empty($_FILES['chat_voice']['name'])) {
            $voice = $this->uploadVoiceMessage($_FILES['chat_voice']);
            if (!$voice) {
                echo json_encode(['success' => false, 'error' => 'Invalid voice upload.']);
                exit;
            }
            $id = $this->messageModel->sendMedia($userId, $receiverId, 'voice', $voice, $voiceDuration);
            $msg = $this->messageModel->getMessageById($id);
            $time = $msg ? format_chat_time((string)$msg['created_at']) : format_chat_time((new DateTime('now'))->format('Y-m-d H:i:s'));
            echo json_encode([
                'success' => true,
                'id' => $id,
                'message_type' => 'voice',
                'media_file' => $voice,
                'media_duration' => $voiceDuration,
                'time' => $time,
                'is_read' => 0
            ]);
            exit;
        }

        if ($message === '' || mb_strlen($message) > 1000) {
            echo json_encode(['success' => false]);
            exit;
        }

        $id = $this->messageModel->send($userId, $receiverId, $message);
        $msg = $this->messageModel->getMessageById($id);
        $time = $msg ? format_chat_time((string)$msg['created_at']) : format_chat_time((new DateTime('now'))->format('Y-m-d H:i:s'));

        echo json_encode([
            'success' => true,
            'id' => $id,
            'message_type' => 'text',
            'message' => $message,
            'time' => $time,
            'is_read' => 0
        ]);
        exit;
    }

    public function pollMessages(): void {
        $this->requireAuth();
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $userId = (int)$_SESSION['user_id'];
        if (!$this->pollGate($userId)) {
            echo json_encode(['messages' => [], 'unread' => $this->messageModel->getUnreadCount($userId), 'seen_up_to' => 0]);
            exit;
        }

        $otherId = (int)($_GET['with'] ?? 0);
        $lastId = (int)($_GET['last_id'] ?? 0);

        if ($otherId <= 0 || !$this->userModel->findById($otherId)) {
            echo json_encode(['messages' => [], 'unread' => $this->messageModel->getUnreadCount($userId), 'seen_up_to' => 0]);
            exit;
        }

        $this->messageModel->markRead($otherId, $userId);
        $messages = $this->messageModel->getNewMessages($userId, $otherId, $lastId);
        $unread = $this->messageModel->getUnreadCount($userId);
        $seenUpTo = $this->messageModel->getLastReadMessageId($userId, $otherId);

        echo json_encode([
            'messages' => $messages,
            'unread' => $unread,
            'seen_up_to' => $seenUpTo
        ]);
        exit;
    }
}
