<?php
$currentUserId     = $_SESSION['user_id'] ?? null;
$currentUsername   = $_SESSION['username'] ?? '';
$currentFullName   = $_SESSION['full_name'] ?? '';
$currentAvatar     = $_SESSION['profile_image'] ?? 'default.png';

$unreadMsgCount = isset($unreadMsgCount) ? (int)$unreadMsgCount : 0;
$headerNotifications = isset($headerNotifications) && is_array($headerNotifications) ? $headerNotifications : [];
$headerNotifUnreadCount = isset($headerNotifUnreadCount) ? (int)$headerNotifUnreadCount : 0;
$headerPendingRequests = isset($headerPendingRequests) && is_array($headerPendingRequests) ? $headerPendingRequests : [];
$headerPendingCount = isset($headerPendingCount) ? (int)$headerPendingCount : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

<meta property="og:title" content="Texsico - Social Networking Platform">
<meta property="og:description" content="Connect, share, and chat with friends on Texsico.">
<meta property="og:image" content="https://texsico.xyz/assets/images/preview.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:url" content="https://texsico.xyz<?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Texsico">
    <link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">

<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<meta name="theme-color" content="#355cff">
<link rel="manifest" href="/site.webmanifest">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Texsico — Minimal Social Messaging</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script>(function(){var m=localStorage.getItem('texsico_theme_mode')||'auto';var d=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;document.documentElement.dataset.theme=(m==='light'||m==='dark')?m:(d?'dark':'light');document.documentElement.dataset.themeMode=m;})();</script>
  <link rel="stylesheet" href="assets/css/app.css?v=<?= asset_version('assets/css/app.css') ?>">
  <script defer src="assets/js/theme.js?v=<?= asset_version('assets/js/theme.js') ?>"></script>
</head>

<body class="page-<?= htmlspecialchars((string)($page ?? 'app'), ENT_QUOTES, 'UTF-8') ?>">
  <?php if ($currentUserId): ?>
    <form id="globalLogoutForm" class="logout-inline-form" action="index.php?page=logout" method="POST" style="display:none;">
      <?= csrf_input() ?>
    </form>
  <?php endif; ?>


  <div class="floating-action-stack">
    <?php if ($currentUserId): ?>
      <button type="button" class="notification-toggle-btn" id="notificationToggleBtn" aria-label="Open notifications">
        <i class="fa-regular fa-bell"></i>
        <?php if (($headerNotifUnreadCount + $headerPendingCount) > 0): ?>
          <span class="floating-badge" id="notificationBadge"><?= (int)($headerNotifUnreadCount + $headerPendingCount) ?></span>
        <?php endif; ?>
      </button>
    <?php endif; ?>
    <button type="button" class="theme-toggle-btn" id="themeToggleBtn" aria-label="Toggle theme">
      <i class="fa-solid fa-circle-half-stroke"></i>
    </button>
  </div>

  <?php if ($currentUserId): ?>
    <aside class="notification-drawer" id="notificationDrawer" aria-hidden="true">
      <div class="notification-drawer-header">
        <div>
          <div class="notification-drawer-kicker">Activity</div>
          <div class="notification-drawer-title">Notifications</div>
        </div>
        <button type="button" class="notification-close-btn" onclick="closeNotificationDrawer()"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <?php if (!empty($headerPendingRequests)): ?>
        <div class="notification-group-label">Friend requests</div>
        <div class="notification-request-list">
          <?php foreach ($headerPendingRequests as $requestUser): ?>
            <div class="notification-request-item">
              <img decoding="async" loading="lazy" src="<?= (!empty($requestUser['profile_image']) && $requestUser['profile_image'] !== 'default.png') ? 'index.php?asset=avatar&f=' . urlencode($requestUser['profile_image']) : default_avatar_data_uri($requestUser['full_name'] ?: $requestUser['username'], 96) ?>" alt="" class="notification-avatar">
              <div class="notification-copy">
                <strong><?= htmlspecialchars($requestUser['full_name']) ?></strong>
                <span>@<?= htmlspecialchars($requestUser['username']) ?></span>
              </div>
              <?= friend_action_button((int)$requestUser['id'], 'incoming_pending', true) ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="notification-group-label">Recent activity</div>
      <div class="notification-list">
        <?php if (!empty($headerNotifications)): ?>
          <?php foreach ($headerNotifications as $note): ?>
            <?php
              $noteLink = 'index.php?page=profile&id=' . (int)$note['actor_id'];
              if (in_array($note['type'], ['post_like', 'post_comment'], true) && !empty($note['resource_id'])) {
                  $noteLink = 'index.php?page=feed#post-' . (int)$note['resource_id'];
              }
            ?>
            <a href="<?= htmlspecialchars($noteLink) ?>" class="notification-item <?= !empty($note['is_read']) ? '' : 'is-unread' ?>">
              <img decoding="async" loading="lazy" src="<?= (!empty($note['actor_image']) && $note['actor_image'] !== 'default.png') ? 'index.php?asset=avatar&f=' . urlencode($note['actor_image']) : default_avatar_data_uri($note['actor_name'] ?: $note['actor_username'], 96) ?>" alt="" class="notification-avatar">
              <div class="notification-copy">
                <strong><?= htmlspecialchars($note['actor_name']) ?></strong>
                <span><?= htmlspecialchars($note['message']) ?></span>
                <small><?= htmlspecialchars(app_time_ago((string)$note['created_at'])) ?></small>
              </div>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="notification-empty-state">
            <i class="fa-regular fa-bell-slash"></i>
            <span>No notifications yet.</span>
          </div>
        <?php endif; ?>
      </div>
    </aside>
  <?php endif; ?>

  <div class="modal-overlay" id="confirmModal">
    <div class="modal" style="max-width:420px;">
      <div class="modal-header">
        <span class="modal-title" id="confirmModalTitle">Confirm action</span>
        <button class="modal-close" type="button" onclick="closeConfirmModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <div class="confirm-copy" id="confirmModalCopy">Are you sure?</div>
        <div class="confirm-actions">
          <button class="btn btn-ghost" type="button" onclick="closeConfirmModal()">Cancel</button>
          <button class="btn btn-danger" type="button" id="confirmModalActionBtn">Continue</button>
        </div>
      </div>
    </div>
  </div>

  <div class="lightbox-overlay" id="imageLightbox">
    <div class="lightbox-card">
      <button type="button" class="lightbox-close" onclick="closeLightbox()"><i class="fa-solid fa-xmark"></i></button>
      <img decoding="async" loading="lazy" src="" alt="Expanded media" class="lightbox-image" id="lightboxImage">
    </div>
  </div>

  <?php if ($currentUserId): ?>
    <nav class="mobile-nav">
      <div class="mobile-nav-inner">
        <a href="index.php?page=feed" class="mobile-nav-btn <?= ($page ?? '') === 'feed' ? 'active' : '' ?>">
          <i class="fa-solid fa-house"></i>
          <span>Home</span>
        </a>
        <a href="index.php?page=search" class="mobile-nav-btn <?= ($page ?? '') === 'search' ? 'active' : '' ?>">
          <i class="fa-solid fa-users"></i>
          <span>People</span>
        </a>
        <a href="index.php?page=chat" class="mobile-nav-btn <?= ($page ?? '') === 'chat' ? 'active' : '' ?>">
          <i class="fa-solid fa-message"></i>
          <span>Chat</span>
          <?php if (!empty($unreadMsgCount) && $unreadMsgCount > 0): ?>
            <span class="mob-badge"><?= $unreadMsgCount ?></span>
          <?php endif; ?>
        </a>
        <a href="index.php?page=profile" class="mobile-nav-btn <?= ($page ?? '') === 'profile' ? 'active' : '' ?>">
          <i class="fa-regular fa-user"></i>
          <span>Profile</span>
        </a>
        <button type="button" class="mobile-nav-btn logout-btn" onclick="document.getElementById('globalLogoutForm').submit()">
          <i class="fa-solid fa-right-from-bracket"></i>
          <span>Logout</span>
        </button>
      </div>
    </nav>
  <?php endif; ?>

  <div class="toast-container" id="toastContainer"></div>

  <script>
    let confirmModalCallback = null;

    function refreshViewportUnit() {
      const height = window.visualViewport ? window.visualViewport.height : window.innerHeight;
      document.documentElement.style.setProperty('--vvh', height + 'px');
    }

    function getTextareaBaseHeight(el) {
      if (!el) return 0;
      const styles = window.getComputedStyle(el);
      const rows = Math.max(parseInt(el.getAttribute('rows') || '1', 10) || 1, 1);
      const lineHeight = parseFloat(styles.lineHeight) || ((parseFloat(styles.fontSize) || 16) * 1.35);
      const paddingY = (parseFloat(styles.paddingTop) || 0) + (parseFloat(styles.paddingBottom) || 0);
      const borderY = (parseFloat(styles.borderTopWidth) || 0) + (parseFloat(styles.borderBottomWidth) || 0);
      const cssMinHeight = parseFloat(styles.minHeight) || 0;
      return Math.ceil(Math.max((lineHeight * rows) + paddingY + borderY, cssMinHeight));
    }

    function autoGrowTextarea(el) {
      if (!el) return;
      const minHeight = parseInt(el.dataset.minHeight || '0', 10) || getTextareaBaseHeight(el) || 0;
      const maxHeight = parseInt(el.dataset.maxHeight || '170', 10);
      el.style.height = 'auto';
      const next = Math.min(Math.max(el.scrollHeight || 0, minHeight), maxHeight);
      el.style.height = next + 'px';
      el.classList.toggle('is-multiline', next > (minHeight + 8));
    }

    function initAutoGrowTextareas(root = document) {
      root.querySelectorAll('textarea[data-autogrow]').forEach(function(el) {
        const minHeight = getTextareaBaseHeight(el);
        if (minHeight) {
          el.dataset.minHeight = String(minHeight);
        }
        if (!el.dataset.autogrowBound) {
          el.addEventListener('input', function() { autoGrowTextarea(el); });
          el.addEventListener('focus', function() { autoGrowTextarea(el); });
          el.dataset.autogrowBound = '1';
        }
        autoGrowTextarea(el);
      });
    }

    document.addEventListener('DOMContentLoaded', function() {
      refreshViewportUnit();
      initAutoGrowTextareas();
      const avatarBtn = document.getElementById('navAvatarBtn');
      const dropdown = document.getElementById('navDropdown');
      const themeBtn = document.getElementById('themeToggleBtn');
      const notificationBtn = document.getElementById('notificationToggleBtn');
      const notificationDrawer = document.getElementById('notificationDrawer');
      if (typeof applySavedThemeMode === 'function') { applySavedThemeMode(); }

      if (avatarBtn && dropdown) {
        avatarBtn.addEventListener('click', function(e) {
          e.stopPropagation();
          dropdown.classList.toggle('show');
        });
        document.addEventListener('click', function() {
          dropdown.classList.remove('show');
        });
      }

      if (themeBtn) {
        themeBtn.addEventListener('click', toggleThemeMode);
      }

      if (notificationBtn && notificationDrawer) {
        notificationBtn.addEventListener('click', function(e) {
          e.stopPropagation();
          notificationDrawer.classList.toggle('open');
          if (notificationDrawer.classList.contains('open')) {
            markNotificationsRead();
          }
        });

        notificationDrawer.addEventListener('click', function(e) {
          e.stopPropagation();
        });

        document.addEventListener('click', function() {
          notificationDrawer.classList.remove('open');
        });
      }

      document.addEventListener('click', function(e) {
        const target = e.target.closest('.js-lightbox-image');
        if (!target) return;
        const src = target.getAttribute('data-fullsrc') || target.getAttribute('src');
        if (src) openLightbox(src);
      });

      document.querySelectorAll('.modal-overlay').forEach(function(modal) {
        modal.addEventListener('click', function(e) {
          if (e.target === modal && modal.id === 'confirmModal') {
            closeConfirmModal();
          }
          if (e.target === modal && modal.id === 'imageLightbox') {
            closeLightbox();
          }
        });
      });
    });

    window.addEventListener('pageshow', function() {
      refreshViewportUnit();
      initAutoGrowTextareas();
    });

    window.addEventListener('resize', refreshViewportUnit, { passive: true });
    window.addEventListener('orientationchange', refreshViewportUnit, { passive: true });
    if (window.visualViewport) {
      window.visualViewport.addEventListener('resize', refreshViewportUnit, { passive: true });
      window.visualViewport.addEventListener('scroll', refreshViewportUnit, { passive: true });
    }


    function defaultAvatarUrl(name, size = 128) {
      name = (name || 'Texsico User').trim() || 'Texsico User';
      const parts = name.split(/\s+/).filter(Boolean);
      const initials = (parts.slice(0, 2).map(p => p[0]?.toUpperCase() || '').join('') || name.slice(0, 2).toUpperCase());
      const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 ${size} ${size}"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#53d4ff" offset="0%"/><stop stop-color="#8f88ff" offset="100%"/></linearGradient></defs><rect width="${size}" height="${size}" rx="${size / 2}" fill="url(#g)"/><text x="50%" y="53%" dominant-baseline="middle" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="${Math.max(22, Math.floor(size * 0.34))}" font-weight="700" fill="#fff">${initials.replace(/[<&>]/g, '')}</text></svg>`;
      return 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svg)));
    }

    function openConfirmModal(message, onConfirm, title = 'Confirm action', buttonText = 'Continue') {
      const modal = document.getElementById('confirmModal');
      const titleEl = document.getElementById('confirmModalTitle');
      const copyEl = document.getElementById('confirmModalCopy');
      const actionBtn = document.getElementById('confirmModalActionBtn');
      if (!modal || !titleEl || !copyEl || !actionBtn) return;
      titleEl.textContent = title;
      copyEl.textContent = message;
      actionBtn.textContent = buttonText;
      confirmModalCallback = typeof onConfirm === 'function' ? onConfirm : null;
      actionBtn.onclick = function() {
        const fn = confirmModalCallback;
        closeConfirmModal();
        if (fn) fn();
      };
      modal.classList.add('open');
    }

    function closeConfirmModal() {
      document.getElementById('confirmModal')?.classList.remove('open');
      confirmModalCallback = null;
    }

    function openLightbox(src) {
      const img = document.getElementById('lightboxImage');
      const box = document.getElementById('imageLightbox');
      if (!img || !box) return;
      img.src = src;
      box.classList.add('open');
    }

    function closeLightbox() {
      const box = document.getElementById('imageLightbox');
      const img = document.getElementById('lightboxImage');
      if (img) img.src = '';
      box?.classList.remove('open');
    }


    function closeNotificationDrawer() {
      document.getElementById('notificationDrawer')?.classList.remove('open');
    }

    function markNotificationsRead() {
      const badge = document.getElementById('notificationBadge');
      if (badge) badge.remove();
      fetch('index.php?page=notifications.read', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: `csrf_token=${encodeURIComponent(<?= json_encode(csrf_token()) ?>)}`
      }).catch(() => {});
    }

    function showToast(msg, type = 'success') {
      const c = document.getElementById('toastContainer');
      const t = document.createElement('div');
      t.className = 'toast ' + type;
      t.innerHTML = `<i class="fa-solid ${type==='success'?'fa-check-circle':'fa-circle-xmark'}"></i> ${msg}`;
      c.appendChild(t);
      setTimeout(() => t.remove(), 3100);
    }
  </script>