<?php
$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old']    ?? [];
unset($_SESSION['errors'], $_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Join Texsico — Create Account</title>

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
      <p class="auth-tagline">Create your space and start posting, chatting, and sharing with clarity.</p>
      <div class="auth-features">
        <div class="auth-feature">
          <div class="auth-feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
          <div class="auth-feature-text"><strong>Secure accounts</strong> — hashed passwords and clean sessions</div>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon"><i class="fa-solid fa-pen-nib"></i></div>
          <div class="auth-feature-text"><strong>Share clearly</strong> — posts, photos, prompts, and updates</div>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon"><i class="fa-solid fa-people-group"></i></div>
          <div class="auth-feature-text"><strong>Find your people</strong> — browse profiles and message fast</div>
        </div>
      </div>
    </div>
  </div>
  <div class="auth-right">
    <div class="auth-form-box">
     <div class="auth-mobile-shell">
  <div class="auth-mobile-brand">
    <div class="auth-mobile-logo"><img src="apple-touch-icon.png" alt="Texsico logo" class="auth-mobile-logo-icon"><strong class="auth-mobile-word">Tex<span>s</span>ico</strong></div>
    <div class="auth-mobile-note"><i class="fa-solid fa-sparkles"></i> Create your social space</div>
  </div>

  <div class="auth-mobile-marquee" aria-hidden="true">
    <div class="auth-mobile-pill"><i class="fa-solid fa-user-plus"></i><span><strong>Create profile</strong><br>set up your identity fast</span></div>
    <div class="auth-mobile-pill"><i class="fa-solid fa-sliders"></i><span><strong>Filter your feed</strong><br>read the way you want</span></div>
    <div class="auth-mobile-pill"><i class="fa-solid fa-comments"></i><span><strong>Start chatting</strong><br>search and reply faster</span></div>
  </div>
</div>

<div class="auth-top-link">
  <span>Already a member?</span>
  <a href="index.php?page=login" class="auth-link">Sign in</a>
</div>

<div class="auth-form-head">
  <div class="auth-eyebrow">Join Texsico</div>
  <h1 class="auth-form-title">Create your account</h1>
  <p class="auth-form-subtitle">Set up your profile and jump into a cleaner, calmer social experience.</p>
</div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <?php foreach ($errors as $e): ?>
            <div><?= htmlspecialchars($e) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form action="index.php?page=register" method="POST" id="registerForm" novalidate>
        <?= csrf_input() ?>
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" placeholder="Your full name" value="<?= htmlspecialchars($old['full_name'] ?? '') ?>" maxlength="80" required>
        </div>
        <div class="form-group">
          <label class="form-label">Username</label>
          <div style="font-size:12px; color:var(--text-dim); margin-bottom:6px;">3 to 20 letters, numbers, or underscores.</div>
          <input type="text" name="username" class="form-control" placeholder="Choose a username" value="<?= htmlspecialchars($old['username'] ?? '') ?>" maxlength="20" pattern="[A-Za-z0-9_]+" title="Use 3 to 20 letters, numbers, or underscores." required>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="you@email.com" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" id="registerPassword" class="form-control" placeholder="Min. 8 characters" minlength="8" required aria-describedby="passwordRules">
          <div class="password-checklist" id="passwordRules" aria-live="polite">
            <div class="password-checklist-title">Use a stronger password:</div>
            <div class="password-rule" data-rule="length">
              <i class="fa-solid fa-circle"></i>
              <span>At least 8 characters</span>
            </div>
            <div class="password-rule" data-rule="numberSymbol">
              <i class="fa-solid fa-circle"></i>
              <span>At least one number or symbol</span>
            </div>
            <div class="password-rule" data-rule="caseMix">
              <i class="fa-solid fa-circle"></i>
              <span>Lowercase and uppercase letters</span>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="confirm_password" id="registerConfirmPassword" class="form-control" placeholder="Repeat password" required>
          <div class="password-match" id="passwordMatchHint" aria-live="polite"></div>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:8px; padding:13px;">
          Create Account <i class="fa-solid fa-arrow-right"></i>
        </button>
      </form>
    </div>
  </div>
</div>


<script>
(function(){
  const form = document.getElementById('registerForm');
  const password = document.getElementById('registerPassword');
  const confirm = document.getElementById('registerConfirmPassword');
  const matchHint = document.getElementById('passwordMatchHint');
  if (!form || !password || !confirm) return;

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

