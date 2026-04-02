<?php
$errors = $_SESSION['errors'] ?? [];
$error = $_SESSION['error'] ?? null;
$status = $_SESSION['status'] ?? null;
$flashToken = $_SESSION['pw_reset_token'] ?? null;
unset($_SESSION['errors'], $_SESSION['error'], $_SESSION['status'], $_SESSION['pw_reset_token']);
$tokenValue = $flashToken ?? ($token ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Set New Password — Texsico</title>

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
      <p class="auth-tagline">Choose a new password that is strong enough for a clean reset.</p>
      <div class="auth-features">
        <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-lock"></i></div><div class="auth-feature-text"><strong>Short expiry</strong> — reset links expire quickly for safety</div></div>
        <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-check-double"></i></div><div class="auth-feature-text"><strong>One-time use</strong> — old reset links stop working after a successful change</div></div>
        <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-bolt"></i></div><div class="auth-feature-text"><strong>Fast recovery</strong> — sign back in immediately after setting a new password</div></div>
      </div>
    </div>
  </div>
  <div class="auth-right">
    <div class="auth-form-box">
      <div class="auth-top-link">
        <span>Need another link?</span>
        <a href="index.php?page=forgot-password" class="auth-link">Request again</a>
      </div>

      <div class="auth-form-head">
        <div class="auth-eyebrow">New password</div>
        <h1 class="auth-form-title">Choose a new password</h1>
        <p class="auth-form-subtitle">Use a password with uppercase, lowercase, and at least one number or symbol.</p>
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

      <?php if (!$tokenIsValid): ?>
        <div class="alert alert-error"><i class="fa-solid fa-link-slash"></i> That reset link is invalid or has expired.</div>
        <a href="index.php?page=forgot-password" class="btn btn-primary" style="width:100%; justify-content:center; padding:13px;">Request a new reset link</a>
      <?php else: ?>
        <form action="index.php?page=reset-password.save" method="POST" id="resetPasswordForm" novalidate>
          <?= csrf_input() ?>
          <input type="hidden" name="token" value="<?= htmlspecialchars($tokenValue) ?>">
          <div class="form-group">
            <label class="form-label">New password</label>
            <input type="password" name="password" id="resetPassword" class="form-control" placeholder="Choose a new password" required autofocus>
          </div>
          <div class="password-checklist">
            <div class="password-checklist-title">Password checklist</div>
            <div class="password-rule" data-rule="length"><i class="fa-solid fa-circle"></i> At least 8 characters</div>
            <div class="password-rule" data-rule="numberSymbol"><i class="fa-solid fa-circle"></i> At least one number or symbol</div>
            <div class="password-rule" data-rule="caseMix"><i class="fa-solid fa-circle"></i> Lowercase and uppercase letters</div>
          </div>
          <div class="form-group" style="margin-top:14px;">
            <label class="form-label">Confirm password</label>
            <input type="password" name="confirm_password" id="resetConfirmPassword" class="form-control" placeholder="Retype your new password" required>
            <div id="resetPasswordMatchHint" class="password-match"></div>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:8px; padding:13px;">
            Save new password <i class="fa-solid fa-shield-halved"></i>
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(function(){
  const form = document.getElementById('resetPasswordForm');
  const password = document.getElementById('resetPassword');
  const confirm = document.getElementById('resetConfirmPassword');
  const matchHint = document.getElementById('resetPasswordMatchHint');
  if (!form || !password || !confirm || !matchHint) return;

  const rules = {
    length: value => value.length >= 8,
    numberSymbol: value => /\d|[^A-Za-z0-9]/.test(value),
    caseMix: value => /[a-z]/.test(value) && /[A-Z]/.test(value)
  };

  function setRuleState(name, ok) {
    const row = document.querySelector('[data-rule="' + name + '"]');
    if (!row) return;
    row.classList.toggle('is-valid', !!ok);
    row.classList.toggle('is-invalid', !ok && password.value.length > 0);
  }

  function updatePasswordRules() {
    const value = password.value || '';
    Object.entries(rules).forEach(([name, test]) => setRuleState(name, test(value)));

    if (!confirm.value) {
      matchHint.textContent = '';
      matchHint.className = 'password-match';
      return;
    }

    const same = value === confirm.value;
    matchHint.textContent = same ? 'Passwords match.' : 'Passwords do not match yet.';
    matchHint.className = 'password-match ' + (same ? 'is-valid' : 'is-invalid');
  }

  password.addEventListener('input', updatePasswordRules);
  confirm.addEventListener('input', updatePasswordRules);
  form.addEventListener('submit', updatePasswordRules);
  updatePasswordRules();
})();
</script>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
