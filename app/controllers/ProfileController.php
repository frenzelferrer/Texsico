<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/CommentModel.php';
require_once __DIR__ . '/../models/MessageModel.php';
require_once __DIR__ . '/../models/FriendshipModel.php';

class ProfileController {
    private UserModel $userModel;
    private PostModel $postModel;
    private CommentModel $commentModel;
    private MessageModel $messageModel;
    private FriendshipModel $friendshipModel;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->postModel = new PostModel();
        $this->commentModel = new CommentModel();
        $this->messageModel = new MessageModel();
        $this->friendshipModel = new FriendshipModel();
    }

    private function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
    }

    public function showProfile(): void {
        $this->requireAuth();
        $currentUserId = (int)$_SESSION['user_id'];
        $profileId = isset($_GET['id']) ? (int)$_GET['id'] : $currentUserId;

        $profileUser = $this->userModel->findById($profileId);
        if (!$profileUser) {
            header('Location: index.php?page=feed');
            exit;
        }

        $friendshipState = $this->friendshipModel->getState($currentUserId, $profileId);
        $canViewPosts = ($profileId === $currentUserId) || $this->friendshipModel->areFriends($currentUserId, $profileId);
        $posts = $canViewPosts ? $this->postModel->getPostsByUser($profileId, $currentUserId) : [];
        $comments = $this->commentModel->getByPostIds(array_column($posts, 'id'));
        $unreadMsgCount = $this->messageModel->getUnreadCount($currentUserId);
        $friendCount = $this->friendshipModel->getFriendCount($profileId);
        extract(app_get_header_view_data($currentUserId), EXTR_OVERWRITE);

        require __DIR__ . '/../views/profile/index.php';
    }

    public function updateProfile(): void {
        $this->requireAuth();
        verify_csrf_request();

        if (!app_rate_limit('profile_update_' . (int)$_SESSION['user_id'], 10, 600)) {
            $_SESSION['error'] = 'Profile updates are happening too quickly. Please try again shortly.';
            header('Location: index.php?page=profile');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $existingUser = $this->userModel->findById($userId);
        $fullName = app_normalize_single_line((string)($_POST['full_name'] ?? ''), 80);
        $bio = app_normalize_multiline((string)($_POST['bio'] ?? ''), 300);

        if ($fullName === '' || app_strlen($fullName) > 80 || app_strlen($bio) > 300) {
            $_SESSION['error'] = 'Please keep your profile details within the allowed length.';
            header('Location: index.php?page=profile');
            exit;
        }

        $imageName = null;
        $coverName = null;

        if (!empty($_FILES['profile_image']['name'])) {
            $imageName = optimized_image_upload($_FILES['profile_image'], 'avatars', 'avatar_' . $userId, [
                'max_width' => 512,
                'max_height' => 512,
                'quality' => 76,
                'max_file_size' => 5,
                'strip_animation' => true,
            ]);

            if ($imageName === null) {
                $_SESSION['error'] = 'Profile image upload failed. Use JPG, PNG, GIF, or WEBP under 5MB.';
                header('Location: index.php?page=profile');
                exit;
            }
        }

        if (!empty($_FILES['cover_photo']['name'])) {
            $coverName = optimized_image_upload($_FILES['cover_photo'], 'covers', 'cover_' . $userId, [
                'max_width' => 1600,
                'max_height' => 900,
                'quality' => 74,
                'max_file_size' => 5,
            ]);

            if ($coverName === null) {
                $_SESSION['error'] = 'Cover photo upload failed. Use JPG, PNG, GIF, or WEBP under 5MB.';
                header('Location: index.php?page=profile');
                exit;
            }
        }

        $this->userModel->updateProfile($userId, $fullName, $bio, $imageName, $coverName);
        $_SESSION['full_name'] = $fullName;
        if ($imageName) {
            remove_uploaded_asset('avatar', $existingUser['profile_image'] ?? null);
            $_SESSION['profile_image'] = $imageName;
        }
        if ($coverName) {
            remove_uploaded_asset('cover', $existingUser['cover_photo'] ?? null);
        }

        header('Location: index.php?page=profile');
        exit;
    }

    public function searchUsers(): void {
        $this->requireAuth();
        $q = app_normalize_single_line((string)($_GET['q'] ?? ''), 100);
        $userId = (int)$_SESSION['user_id'];
        $results = $q !== '' ? $this->userModel->searchUsers($q, $userId) : [];
        $allUsers = $q === '' ? $this->userModel->getAllExcept($userId) : [];
        $targets = array_merge($results, $allUsers);
        $stateMap = $this->friendshipModel->getStateMap($userId, array_column($targets, 'id'));
        foreach ($results as &$user) {
            $user['friendship_state'] = $stateMap[(int)$user['id']] ?? 'none';
        }
        unset($user);
        foreach ($allUsers as &$user) {
            $user['friendship_state'] = $stateMap[(int)$user['id']] ?? 'none';
        }
        unset($user);
        $unreadMsgCount = $this->messageModel->getUnreadCount($userId);
        extract(app_get_header_view_data($userId), EXTR_OVERWRITE);
        require __DIR__ . '/../views/profile/search.php';
    }
}
