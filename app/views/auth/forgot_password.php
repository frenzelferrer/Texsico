<?php
$error = $_SESSION['error'] ?? null;
$status = $_SESSION['status'] ?? null;
unset($_SESSION['error'], $_SESSION['status']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — Texsico</title>

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
<div class="auth-page auth-page-simple">
  <div class="auth-left">
    <div class="auth-left-content">
      <div class="auth-logo-big"><img src="apple-touch-icon.png" alt="Texsico logo" class="auth-logo-icon"><strong class="auth-logo-word">Tex<span>s</span>ico</strong></div>
      <p class="auth-tagline">Securely recover your account with a time-limited reset link.</p>
      <div class="auth-features">
        <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-envelope"></i></div><div class="auth-feature-text"><strong>Private requests</strong> — the same message shows whether the email exists or not</div></div>
        <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-key"></i></div><div class="auth-feature-text"><strong>One-time tokens</strong> — reset links expire quickly and cannot be reused</div></div>
        <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-shield-halved"></i></div><div class="auth-feature-text"><strong>Strong password rules</strong> — lowercase, uppercase, and a number or symbol</div></div>
      </div>
    </div>
  </div>
  <div class="auth-right">
    <div class="auth-form-box">
      <div class="auth-top-link">
        <span>Remembered it?</span>
        <a href="index.php?page=login" class="auth-link">Back to sign in</a>
      </div>

      <div class="auth-form-head">
        <div class="auth-eyebrow">Password recovery</div>
        <h1 class="auth-form-title">Reset your password</h1>
        <p class="auth-form-subtitle">Enter your email address and we will send a reset link if the account exists.</p>
      </div>

      <?php if ($status): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($status) ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form action="index.php?page=forgot-password.send" method="POST" id="forgotPasswordForm">
        <?= csrf_input() ?>
        <div class="form-group">
          <label class="form-label">Email address</label>
          <input type="email" name="email" class="form-control" placeholder="Enter your email" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary" id="forgotPasswordSubmit" style="width:100%; justify-content:center; margin-top:8px; padding:13px;">
          <span class="js-submit-label">Send reset link</span> <i class="fa-solid fa-paper-plane"></i>
        </button>
      </form>

      <div class="auth-divider"><span>Need a new account?</span></div>
      <a href="index.php?page=register" class="btn btn-ghost" style="width:100%; justify-content:center; padding:13px;">
        Create an account
      </a>
    </div>
  </div>
</div>

<script>
(function(){
  const form = document.getElementById('forgotPasswordForm');
  const button = document.getElementById('forgotPasswordSubmit');
  if (!form || !button) return;

  let isSubmitting = false;
  form.addEventListener('submit', function(event) {
    if (isSubmitting) {
      event.preventDefault();
      return;
    }

    isSubmitting = true;
    button.disabled = true;
    button.classList.add('is-submitting');
    const label = button.querySelector('.js-submit-label');
    if (label) {
      label.textContent = 'Sending...';
    }
  });
})();
</script>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
