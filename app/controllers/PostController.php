<?php
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/CommentModel.php';
require_once __DIR__ . '/../models/LikeModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/MessageModel.php';

class PostController {
    private PostModel $postModel;
    private CommentModel $commentModel;
    private LikeModel $likeModel;
    private UserModel $userModel;
    private MessageModel $messageModel;

    public function __construct() {
        $this->postModel = new PostModel();
        $this->commentModel = new CommentModel();
        $this->likeModel = new LikeModel();
        $this->userModel = new UserModel();
        $this->messageModel = new MessageModel();
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
        $search = mb_substr(trim($_GET['q'] ?? ''), 0, 100);

        $posts = $search !== ''
            ? $this->postModel->search($search, $userId)
            : $this->postModel->getAllPosts($userId);

        $comments = $this->commentModel->getByPostIds(array_column($posts, 'id'));
        $storyUsers = $this->userModel->getAllExcept($userId, 8);
        $suggestedUsers = array_slice($storyUsers, 0, 3);
        $discoverUsers = $this->userModel->getAllExcept($userId, 6);
        $unreadMsgCount = $this->messageModel->getUnreadCount($userId);

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
        $content = trim($_POST['content'] ?? '');

        if (($content === '' && empty($_FILES['image']['name'])) || mb_strlen($content) > 1000) {
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
        $content = trim($_POST['content'] ?? '');

        $post = $this->postModel->getPostById($postId);
        if (!$post || (int)$post['user_id'] !== $userId) {
            if ($this->isAjax()) {
                $this->failJson(['success' => false], 403);
            }
            header('Location: index.php?page=feed');
            exit;
        }
        if ($content === '' || mb_strlen($content) > 1000) {
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
        if ($postId <= 0 || !$this->postModel->getPostById($postId)) {
            $this->failJson();
        }
        $result = $this->likeModel->toggle($postId, $userId);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function addComment(): void {
        $this->requireAuth();
        verify_csrf_request();

        if (!app_rate_limit('comment_add_' . (int)$_SESSION['user_id'], 25, 300)) {
            if ($this->isAjax()) {
                $this->failJson(['success' => false, 'message' => 'You are commenting too quickly.'], 429);
            }
            header('Location: index.php?page=feed');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $postId = (int)($_POST['post_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');

        if ($content === '' || mb_strlen($content) > 1000 || !$this->postModel->getPostById($postId)) {
            if ($this->isAjax()) {
                $this->failJson();
            }
            header('Location: index.php?page=feed');
            exit;
        }

        $commentId = $this->commentModel->create($postId, $userId, $content);

        if ($this->isAjax()) {
            $comment = $this->commentModel->findById($commentId);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'comment' => $comment]);
            exit;
        }
        header('Location: index.php?page=feed');
        exit;
    }

    public function updateComment(): void {
        $this->requireAuth();
        verify_csrf_request();

        $userId = (int)$_SESSION['user_id'];
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');

        if ($commentId <= 0 || $content === '' || mb_strlen($content) > 1000) {
            if ($this->isAjax()) {
                $this->failJson(['success' => false, 'message' => 'Comment content is invalid.']);
            }
            header('Location: index.php?page=feed');
            exit;
        }

        $comment = $this->commentModel->findById($commentId);
        if (!$comment || (int)$comment['user_id'] !== $userId) {
            if ($this->isAjax()) {
                $this->failJson(['success' => false, 'message' => 'Not allowed.'], 403);
            }
            header('Location: index.php?page=feed');
            exit;
        }

        if (!$this->commentModel->update($commentId, $userId, $content)) {
            if ($this->isAjax()) {
                $updated = $this->commentModel->findById($commentId);
                echo json_encode(['success' => true, 'comment' => $updated]);
                exit;
            }
            header('Location: index.php?page=feed');
            exit;
        }

        $updated = $this->commentModel->findById($commentId);
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'comment' => $updated]);
            exit;
        }
        header('Location: index.php?page=feed');
        exit;
    }

    public function deleteComment(): void {
        $this->requireAuth();
        verify_csrf_request();
        $userId = (int)$_SESSION['user_id'];
        $commentId = (int)($_POST['comment_id'] ?? 0);
        if ($commentId <= 0) {
            if ($this->isAjax()) {
                $this->failJson();
            }
            header('Location: index.php?page=feed');
            exit;
        }

        $comment = $this->commentModel->findById($commentId);
        if (!$comment || (int)$comment['user_id'] !== $userId) {
            if ($this->isAjax()) {
                $this->failJson(['success' => false, 'message' => 'Not allowed.'], 403);
            }
            header('Location: index.php?page=feed');
            exit;
        }

        $deleted = $this->commentModel->delete($commentId, $userId);
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => $deleted]);
            exit;
        }
        header('Location: index.php?page=feed');
        exit;
    }
}
