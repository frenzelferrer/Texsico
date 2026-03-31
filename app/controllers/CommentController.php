<?php
require_once __DIR__ . '/../models/CommentModel.php';
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/NotificationModel.php';

class CommentController {
    private CommentModel $commentModel;
    private PostModel $postModel;
    private NotificationModel $notificationModel;

    public function __construct() {
        $this->commentModel = new CommentModel();
        $this->postModel = new PostModel();
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
        $content = app_normalize_multiline((string)($_POST['content'] ?? ''), 1000);
        $post = $this->postModel->getPostById($postId);

        if ($content === '' || app_strlen($content) > 1000 || !$post || !$this->postModel->canUserAccessPost($postId, $userId)) {
            if ($this->isAjax()) {
                $this->failJson(['success' => false, 'message' => 'Not allowed.'], 403);
            }
            header('Location: index.php?page=feed');
            exit;
        }

        $commentId = $this->commentModel->create($postId, $userId, $content);
        if ((int)$post['user_id'] !== $userId) {
            $this->notificationModel->create((int)$post['user_id'], $userId, 'post_comment', $postId, 'commented on your post.');
        }

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
        $content = app_normalize_multiline((string)($_POST['content'] ?? ''), 1000);

        if ($commentId <= 0 || $content === '' || app_strlen($content) > 1000) {
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
                header('Content-Type: application/json');
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
