<?php
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/CommentModel.php';
require_once __DIR__ . '/../models/LikeModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/MessageModel.php';
require_once __DIR__ . '/../models/FriendshipModel.php';
require_once __DIR__ . '/../models/NotificationModel.php';

class PostController {
    private PostModel $postModel;
    private CommentModel $commentModel;
    private LikeModel $likeModel;
    private UserModel $userModel;
    private MessageModel $messageModel;
    private FriendshipModel $friendshipModel;
    private NotificationModel $notificationModel;

    public function __construct() {
        $this->postModel = new PostModel();
        $this->commentModel = new CommentModel();
        $this->likeModel = new LikeModel();
        $this->userModel = new UserModel();
        $this->messageModel = new MessageModel();
        $this->friendshipModel = new FriendshipModel();
        $this->notificationModel = new NotificationModel();
    }

    private function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
    }

    private function isAjax(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function failJson(array $payload = ['success' => false], int $code = 400): never {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    public function showFeed(): void {
        $this->requireAuth();
        $userId = (int)$_SESSION['user_id'];
        $search = app_normalize_single_line((string)($_GET['q'] ?? ''), 100);

        $posts = $search !== ''
            ? $this->postModel->search($search, $userId)
            : $this->postModel->getAllPosts($userId);

        $comments = $this->commentModel->getByPostIds(array_column($posts, 'id'));
        $storyUsers = $this->friendshipModel->getFriends($userId, 8);
        $suggestedUsers = $this->userModel->getAllExcept($userId, 12);
        $discoverUsers = [];
        $stateMap = $this->friendshipModel->getStateMap($userId, array_column($suggestedUsers, 'id'));
        foreach ($suggestedUsers as &$candidate) {
            $candidate['friendship_state'] = $stateMap[(int)$candidate['id']] ?? 'none';
            if ($candidate['friendship_state'] !== 'accepted') {
                $discoverUsers[] = $candidate;
            }
        }
        unset($candidate);
        $discoverUsers = array_slice($discoverUsers, 0, 6);
        $unreadMsgCount = $this->messageModel->getUnreadCount($userId);
        $friendCount = $this->friendshipModel->getFriendCount($userId);

        require __DIR__ . '/../views/feed/index.php';
    }

    public function createPost(): void {
        $this->requireAuth();
        verify_csrf_request();

        if (!app_rate_limit('post_create_' . (int)$_SESSION['user_id'], 12, 300)) {
            if ($this->isAjax()) {
                $this->failJson(['success' => false, 'message' => 'You are posting too quickly. Please wait a bit.'], 429);
            }
            $_SESSION['error'] = 'You are posting too quickly. Please wait a bit.';
            header('Location: index.php?page=feed');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $content = app_normalize_multiline((string)($_POST['content'] ?? ''), 1000);

        if (($content === '' && empty($_FILES['image']['name'])) || app_strlen($content) > 1000) {
            if ($this->isAjax()) {
                $this->failJson(['success' => false, 'message' => 'Add text or an image.']);
            }
            header('Location: index.php?page=feed');
            exit;
        }

        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image = optimized_image_upload($_FILES['image'], 'posts', 'post', [
                'max_width' => 1600,
                'max_height' => 1600,
                'quality' => 74,
                'max_file_size' => 5,
            ]);
        }

        if (!empty($_FILES['image']['name']) && $image === null) {
            if ($this->isAjax()) {
                $this->failJson(['success' => false, 'message' => 'Image upload failed. Use JPG, PNG, GIF, or WEBP under 5MB.']);
            }
            header('Location: index.php?page=feed');
            exit;
        }

        $this->postModel->create($userId, $content, $image);

        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        header('Location: index.php?page=feed');
        exit;
    }

    public function updatePost(): void {
        $this->requireAuth();
        verify_csrf_request();
        $userId = (int)$_SESSION['user_id'];
        $postId = (int)($_POST['post_id'] ?? 0);
        $content = app_normalize_multiline((string)($_POST['content'] ?? ''), 1000);

        $post = $this->postModel->getPostById($postId);
        if (!$post || (int)$post['user_id'] !== $userId) {
            if ($this->isAjax()) {
                $this->failJson(['success' => false], 403);
            }
            header('Location: index.php?page=feed');
            exit;
        }
        if ($content === '' || app_strlen($content) > 1000) {
            if ($this->isAjax()) {
                $this->failJson(['success' => false, 'message' => 'Post content is invalid.']);
            }
            header('Location: index.php?page=feed');
            exit;
        }

        $this->postModel->update($postId, $userId, $content);

        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'content' => $content]);
            exit;
        }
        header('Location: index.php?page=feed');
        exit;
    }

    public function deletePost(): void {
        $this->requireAuth();
        verify_csrf_request();
        $userId = (int)$_SESSION['user_id'];
        $postId = (int)($_POST['post_id'] ?? 0);

        $post = $this->postModel->getPostById($postId);
        if (!$post || (int)$post['user_id'] !== $userId) {
            if ($this->isAjax()) {
                $this->failJson(['success' => false], 403);
            }
            header('Location: index.php?page=feed');
            exit;
        }

        $this->postModel->delete($postId, $userId);
        if (!empty($post['image'])) {
            remove_uploaded_asset('post', $post['image']);
        }

        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        header('Location: index.php?page=feed');
        exit;
    }

    public function toggleLike(): void {
        $this->requireAuth();
        verify_csrf_request();
        $postId = (int)($_POST['post_id'] ?? 0);
        $userId = (int)$_SESSION['user_id'];
        $post = $this->postModel->getPostById($postId);
        if ($postId <= 0 || !$post || !$this->postModel->canUserAccessPost($postId, $userId)) {
            $this->failJson(['success' => false, 'message' => 'Not allowed.'], 403);
        }
        $result = $this->likeModel->toggle($postId, $userId);
        if (($result['liked'] ?? false) && (int)$post['user_id'] !== $userId) {
            $this->notificationModel->create((int)$post['user_id'], $userId, 'post_like', $postId, 'liked your post.');
        }
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

}

