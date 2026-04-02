<?php
$errors = $_SESSION['errors'] ?? [];
$error = $_SESSION['error'] ?? null;
$status = $_SESSION['status'] ?? null;
unset($_SESSION['errors'], $_SESSION['error'], $_SESSION['status']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — Texsico</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
<meta name="theme-color" content="#355cff">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>(function(){var m=localStorage.getItem('texsico_theme_mode')||'auto';var d=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;document.documentElement.dataset.theme=(m==='light'||m==='dark')?m:(d?'dark':'light');document.documentElement.dataset.themeMode=m;})();</script>
<link rel="stylesheet" href="assets/css/auth.css?v=<?= asset_version('assets/css/auth.css') ?>">
<script defer src="assets/js/theme.js?v=<?= asset_version('assets/js/theme.js') ?>"></script>
</head>
<body>
<div class="auth-page">
  <div class="auth-left">
    <div class="auth-left-content">
      <div class="auth-logo-big"><img src="apple-touch-icon.png" alt="Texsico logo" class="auth-logo-icon"><strong class="auth-logo-word">Tex<span>s</span>ico</strong></div>
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
          <div class="auth-mobile-logo"><img src="apple-touch-icon.png" alt="Texsico logo" class="auth-mobile-logo-icon"><strong class="auth-mobile-word">Tex<span>s</span>ico</strong></div>
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

      <?php if ($status): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($status) ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <?php foreach ($errors as $e): ?>
            <div><?= htmlspecialchars($e) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form action="index.php?page=login" method="POST">
        <?= csrf_input() ?>
        <div class="form-group">
          <label class="form-label">Username or Email</label>
          <input type="text" name="identifier" class="form-control" placeholder="Enter username or email" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="password-field">
            <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Enter your password" required>
            <button type="button" class="password-toggle" data-toggle-password="loginPassword" aria-label="Show password" aria-pressed="false" title="Show password">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
        </div>
        <div class="auth-inline-links">
          <a href="index.php?page=forgot-password" class="auth-link">Forgot password?</a>
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

<script>
(function(){
  document.querySelectorAll('[data-toggle-password]').forEach(function(button){
    var inputId = button.getAttribute('data-toggle-password');
    var input = document.getElementById(inputId);
    var icon = button.querySelector('i');
    if (!input || !icon) return;

    button.addEventListener('click', function(){
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      button.setAttribute('title', show ? 'Hide password' : 'Show password');
      button.setAttribute('aria-pressed', show ? 'true' : 'false');
      icon.className = 'fa-solid ' + (show ? 'fa-eye-slash' : 'fa-eye');
    });
  });
})();
</script>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
