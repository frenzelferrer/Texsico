<?php
require_once __DIR__ . '/../models/FriendshipModel.php';
require_once __DIR__ . '/../models/NotificationModel.php';
require_once __DIR__ . '/../models/UserModel.php';

class FriendshipController {
    private FriendshipModel $friendshipModel;
    private NotificationModel $notificationModel;
    private UserModel $userModel;

    public function __construct() {
        $this->friendshipModel = new FriendshipModel();
        $this->notificationModel = new NotificationModel();
        $this->userModel = new UserModel();
    }

    private function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
    }

    private function redirectBack(): never {
        $fallback = 'index.php?page=search';
        $target = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));
        if ($target !== '' && app_origin_matches_current($target)) {
            header('Location: ' . $target);
            exit;
        }
        header('Location: ' . $fallback);
        exit;
    }

    public function request(): void {
        $this->requireAuth();
        verify_csrf_request();

        $userId = (int)$_SESSION['user_id'];
        $targetId = (int)($_POST['user_id'] ?? 0);
        $target = $this->userModel->findById($targetId);
        if ($target && $this->friendshipModel->requestFriend($userId, $targetId)) {
            $state = $this->friendshipModel->getState($userId, $targetId);
            if ($state === 'outgoing_pending') {
                $this->notificationModel->create($targetId, $userId, 'friend_request', $userId, 'sent you a friend request.');
            } elseif ($state === 'accepted') {
                $this->notificationModel->create($targetId, $userId, 'friend_accept', $userId, 'is now your friend.');
            }
        }

        $this->redirectBack();
    }

    public function accept(): void {
        $this->requireAuth();
        verify_csrf_request();

        $userId = (int)$_SESSION['user_id'];
        $otherId = (int)($_POST['user_id'] ?? 0);
        if ($this->friendshipModel->acceptFriend($userId, $otherId)) {
            $this->notificationModel->create($otherId, $userId, 'friend_accept', $userId, 'accepted your friend request.');
        }

        $this->redirectBack();
    }

    public function remove(): void {
        $this->requireAuth();
        verify_csrf_request();
        $userId = (int)$_SESSION['user_id'];
        $otherId = (int)($_POST['user_id'] ?? 0);
        $this->friendshipModel->removeFriend($userId, $otherId);
        $this->redirectBack();
    }
}
