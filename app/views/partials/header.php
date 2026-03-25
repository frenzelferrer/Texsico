<?php
$currentUserId     = $_SESSION['user_id'] ?? null;
$currentUsername   = $_SESSION['username'] ?? '';
$currentFullName   = $_SESSION['full_name'] ?? '';
$currentAvatar     = $_SESSION['profile_image'] ?? 'default.png';

if ($currentUserId) {
  require_once BASE_PATH . '/app/models/MessageModel.php';
  $msgModel = new MessageModel();
  $unreadMsgCount = $msgModel->getUnreadCount($currentUserId);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">

<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<meta name="theme-color" content="#0f172a">
<link rel="manifest" href="/site.webmanifest">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Texsico — Minimal Social Messaging</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script>document.documentElement.dataset.theme = localStorage.getItem('texsico_theme') || 'dark';</script>
  <link rel="stylesheet" href="assets/css/app.css?v=<?= asset_version('assets/css/app.css') ?>">
</head>

<body class="page-<?= htmlspecialchars((string)($page ?? 'app'), ENT_QUOTES, 'UTF-8') ?>">
  <?php if ($currentUserId): ?>
    <form id="globalLogoutForm" class="logout-inline-form" action="index.php?page=logout" method="POST" style="display:none;">
      <?= csrf_input() ?>
    </form>
  <?php endif; ?>


  <button type="button" class="theme-toggle-btn" id="themeToggleBtn" aria-label="Toggle light mode">
    <i class="fa-solid fa-moon"></i>
  </button>

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
      const theme = document.documentElement.dataset.theme || 'dark';
      updateThemeIcon(theme);

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

    function toggleThemeMode() {
      const next = (document.documentElement.dataset.theme || 'dark') === 'dark' ? 'light' : 'dark';
      document.documentElement.dataset.theme = next;
      localStorage.setItem('texsico_theme', next);
      updateThemeIcon(next);
      showToast(next === 'light' ? 'Light mode enabled.' : 'Dark mode enabled.', 'success');
    }

    function updateThemeIcon(theme) {
      const icon = document.querySelector('#themeToggleBtn i');
      if (!icon) return;
      icon.className = theme === 'light' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
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

    function showToast(msg, type = 'success') {
      const c = document.getElementById('toastContainer');
      const t = document.createElement('div');
      t.className = 'toast ' + type;
      t.innerHTML = `<i class="fa-solid ${type==='success'?'fa-check-circle':'fa-circle-xmark'}"></i> ${msg}`;
      c.appendChild(t);
      setTimeout(() => t.remove(), 3100);
    }
  </script>