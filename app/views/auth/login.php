<?php
$errors = $_SESSION['errors'] ?? [];
$error  = $_SESSION['error']  ?? null;
unset($_SESSION['errors'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — Texsico</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/auth.css?v=<?= asset_version('assets/css/auth.css') ?>">
</head>
<body>
<div class="auth-page">
  <div class="auth-left">
    <div class="auth-left-content">
      <div class="auth-logo-big">Tex<span>s</span>ico</div>
      <p class="auth-tagline">A calm, modern space for posts, conversations, and quick updates.</p>
      <div class="auth-features">
        <div class="auth-feature">
          <div class="auth-feature-icon"><i class="fa-solid fa-newspaper"></i></div>
          <div class="auth-feature-text"><strong>Minimal feed</strong> — fewer distractions, better reading flow</div>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon"><i class="fa-solid fa-heart"></i></div>
          <div class="auth-feature-text"><strong>Smart posting</strong> — draft autosave and quick prompt chips</div>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon"><i class="fa-solid fa-message"></i></div>
          <div class="auth-feature-text"><strong>Focused chat</strong> — search messages and reply faster</div>
        </div>
      </div>
    </div>
  </div>
  <div class="auth-right">
    <div class="auth-form-box">
      <div class="auth-mobile-shell">
  <div class="auth-mobile-brand">
    <div class="auth-mobile-logo">Tex<span>s</span>ico</div>
    <div class="auth-mobile-note"><i class="fa-solid fa-comments"></i> Built for smooth conversations</div>
  </div>

  <div class="auth-mobile-marquee" aria-hidden="true">
    <div class="auth-mobile-pill"><i class="fa-solid fa-sliders"></i><span><strong>Clean feed</strong><br>focus on what matters</span></div>
    <div class="auth-mobile-pill"><i class="fa-solid fa-bookmark"></i><span><strong>Saved drafts</strong><br>pick up where you left off</span></div>
    <div class="auth-mobile-pill"><i class="fa-solid fa-magnifying-glass"></i><span><strong>Smart search</strong><br>scan chats in seconds</span></div>
  </div>
</div>

<div class="auth-form-head">
  <div class="auth-eyebrow">Welcome back</div>
  <h1 class="auth-form-title">Welcome back to Texsico</h1>
  <p class="auth-form-subtitle">Sign in to continue your feed, messages, and saved drafts.</p>
</div>

      <?php if ($error): ?>
        <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form action="index.php?page=login" method="POST">
        <?= csrf_input() ?>
        <div class="form-group">
          <label class="form-label">Username or Email</label>
          <input type="text" name="identifier" class="form-control" placeholder="Enter username or email" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:8px; padding:13px;">
          Sign In <i class="fa-solid fa-arrow-right"></i>
        </button>
      </form>

      <div class="auth-divider"><span>New here?</span></div>
      <a href="index.php?page=register" class="btn btn-ghost" style="width:100%; justify-content:center; padding:13px;">
        Create an account
      </a>

    </div>
  </div>
</div>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
