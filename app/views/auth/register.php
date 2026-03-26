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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/auth.css?v=<?= asset_version('assets/css/auth.css') ?>">
</head>
<body>
<div class="auth-page">
  <div class="auth-left">
    <div class="auth-left-content">
      <div class="auth-logo-big">Tex<span>s</span>ico</div>
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
    <div class="auth-mobile-logo">Tex<span>s</span>ico</div>
    <div class="auth-mobile-note"><i class="fa-solid fa-sparkles"></i> Create your social space</div>
  </div>

  <div class="auth-mobile-marquee" aria-hidden="true">
    <div class="auth-mobile-pill"><i class="fa-solid fa-user-plus"></i><span><strong>Create profile</strong><br>set up your identity fast</span></div>
    <div class="auth-mobile-pill"><i class="fa-solid fa-sliders"></i><span><strong>Filter your feed</strong><br>read the way you want</span></div>
    <div class="auth-mobile-pill"><i class="fa-solid fa-comments"></i><span><strong>Start chatting</strong><br>search and reply faster</span></div>
  </div>
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

      <form action="index.php?page=register" method="POST">
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
          <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:8px; padding:13px;">
          Create Account <i class="fa-solid fa-arrow-right"></i>
        </button>
      </form>
    </div>
  </div>
</div>

<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>
