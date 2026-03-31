<?php
require_once __DIR__ . '/../models/NotificationModel.php';

class NotificationController {
    private NotificationModel $notificationModel;

    public function __construct() {
        $this->notificationModel = new NotificationModel();
    }

    private function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
    }

    public function markAllRead(): void {
        $this->requireAuth();
        verify_csrf_request();
        $userId = (int)$_SESSION['user_id'];
        $this->notificationModel->markAllRead($userId);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}
