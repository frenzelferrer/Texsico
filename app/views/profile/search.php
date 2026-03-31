<?php
$page = 'search';
require BASE_PATH . '/app/views/partials/header.php';

function avatarUrlS($img, $name) {
    if (!$img || $img === 'default.png') {
        return default_avatar_data_uri($name, 256);
    }
    return 'index.php?asset=avatar&f=' . urlencode($img);
}
?>

<style>
  .search-form-inline {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 12px;
    align-items: center;
  }

  .search-form-inputwrap {
    position: relative;
    min-width: 0;
  }

  .search-form-inline .form-control {
    width: 100%;
  }

  .search-form-inline .btn {
    min-width: 148px;
    min-height: 54px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    padding-inline: 22px;
  }

  @media (max-width: 720px) {
    .search-form-inline {
      grid-template-columns: 1fr;
    }

    .search-form-inline .btn {
      width: 100%;
      min-width: 0;
    }
  }
</style>

<div class="app-layout">
  <aside class="sidebar">
    <a href="index.php?page=feed" class="sidebar-brand">
      <img src="favicon-32x32.png" alt="Texsico logo" class="sidebar-brand-icon">
      <span class="sidebar-brand-text">Texsico</span>
    </a>
    <a href="index.php?page=profile" class="sidebar-profile">
      <img decoding="async" loading="lazy" src="<?= avatarUrlS($currentAvatar, $currentFullName) ?>" alt="You" class="avatar avatar-sm">
      <div>
        <div class="sidebar-profile-name"><?= htmlspecialchars($currentFullName) ?></div>
        <div class="sidebar-profile-user">@<?= htmlspecialchars($currentUsername) ?></div>
      </div>
    </a>
    <div class="sidebar-divider"></div>
    <ul class="sidebar-menu">
      <li><a href="index.php?page=feed"><span class="menu-icon"><i class="fa-solid fa-house"></i></span> Home</a></li>
      <li><a href="index.php?page=profile"><span class="menu-icon"><i class="fa-regular fa-user"></i></span> My Profile</a></li>
      <li><a href="index.php?page=search" class="active"><span class="menu-icon"><i class="fa-solid fa-users"></i></span> Discover People</a></li>
      <li><a href="index.php?page=chat"><span class="menu-icon"><i class="fa-regular fa-message"></i></span> Messages</a></li>
    </ul>
    <div class="sidebar-divider"></div>
    <form action="index.php?page=logout" method="POST" class="logout-ux-form">
      <?= csrf_input() ?>
      <button type="submit" class="logout-ux-btn" aria-label="Logout">
        <span class="logout-ux-sign" aria-hidden="true">
          <svg viewBox="0 0 512 512">
            <path d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"></path>
          </svg>
        </span>
        <span class="logout-ux-text">Logout</span>
      </button>
    </form>
  </aside>

  <main class="main-content page-shell">
    <section class="page-intro-card">
      <div class="page-intro-eyebrow"><i class="fa-solid fa-users"></i> Texsico directory</div>
      <h1 class="page-intro-title">Find people, open profiles, and start conversations faster.</h1>
      <p class="page-intro-copy">Search people, send a friend request, and unlock posts or chat once both sides are connected.</p>
    </section>

    <div class="search-card search-panel">
      <form action="index.php" method="GET" class="people-search-form search-form-inline">
        <input type="hidden" name="page" value="search">
        <div class="search-form-inputwrap">
          <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-dim);"></i>
          <input
            type="text"
            name="q"
            class="form-control"
            placeholder="Search by name or username…"
            value="<?= htmlspecialchars($q ?? '') ?>"
            style="padding-left:42px;"
            autofocus
          >
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-search"></i> Search
        </button>
      </form>
    </div>

    <?php if (!empty($q)): ?>
      <div style="margin-bottom:16px; color:var(--text-muted); font-size:14px;">
        <?= count($results) ?> result<?= count($results) !== 1 ? 's' : '' ?> for "<strong style="color:var(--text)"><?= htmlspecialchars($q) ?></strong>"
      </div>
    <?php endif; ?>

    <?php if (!empty($results)): ?>
      <div class="people-results-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap:16px;">
        <?php foreach ($results as $u): ?>
          <div class="card people-result-card" style="transition:transform 0.2s,border-color 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
            <div style="padding:20px; text-align:center;">
              <img decoding="async" loading="lazy" src="<?= avatarUrlS($u['profile_image'], $u['full_name']) ?>"
                   alt="<?= htmlspecialchars($u['full_name']) ?>"
                   style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid var(--accent);margin-bottom:12px;">
              <div style="font-weight:700; font-size:16px; font-family:var(--font-display);"><?= htmlspecialchars($u['full_name']) ?></div>
              <div style="color:var(--text-muted); font-size:13px; margin-bottom:16px;">@<?= htmlspecialchars($u['username']) ?></div>
              <div style="display:flex; gap:8px; justify-content:center; flex-wrap:wrap;">
                <a href="index.php?page=profile&id=<?= $u['id'] ?>" class="btn btn-ghost btn-sm">
                  <i class="fa-regular fa-user"></i> View Profile
                </a>
                <?php if (($u['friendship_state'] ?? 'none') === 'accepted'): ?>
                  <a href="index.php?page=chat&with=<?= $u['id'] ?>" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-message"></i> Message
                  </a>
                <?php else: ?>
                  <?= friend_action_button((int)$u['id'], $u['friendship_state'] ?? 'none', true) ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php elseif (!empty($q)): ?>
      <div style="text-align:center; padding:60px; color:var(--text-muted);">
        <i class="fa-solid fa-user-slash" style="font-size:48px; display:block; margin-bottom:16px; opacity:0.3;"></i>
        <p style="font-size:18px; font-weight:600;">No users found</p>
        <p style="font-size:14px; margin-top:6px;">Try a different name or username</p>
      </div>
    <?php else: ?>
      <div style="margin-bottom:16px;">
        <div class="widget-title">Everyone on Texsico</div>
      </div>
      <div class="people-results-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap:16px;">
        <?php foreach ($allUsers as $u): ?>
          <div class="card people-result-card" style="transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
            <div style="padding:20px; text-align:center;">
              <img decoding="async" loading="lazy" src="<?= avatarUrlS($u['profile_image'], $u['full_name']) ?>"
                   alt="<?= htmlspecialchars($u['full_name']) ?>"
                   style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid var(--accent);margin-bottom:12px;">
              <div style="font-weight:700; font-size:16px; font-family:var(--font-display);"><?= htmlspecialchars($u['full_name']) ?></div>
              <div style="color:var(--text-muted); font-size:13px; margin-bottom:16px;">@<?= htmlspecialchars($u['username']) ?></div>
              <div style="display:flex; gap:8px; justify-content:center; flex-wrap:wrap;">
                <a href="index.php?page=profile&id=<?= $u['id'] ?>" class="btn btn-ghost btn-sm">
                  <i class="fa-regular fa-user"></i> Profile
                </a>
                <?php if (($u['friendship_state'] ?? 'none') === 'accepted'): ?>
                  <a href="index.php?page=chat&with=<?= $u['id'] ?>" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-message"></i> Message
                  </a>
                <?php else: ?>
                  <?= friend_action_button((int)$u['id'], $u['friendship_state'] ?? 'none', true) ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <aside class="right-sidebar">
    <?= render_topbar_widgets(true) ?>

    <div class="widget">
      <div class="widget-title"><i class="fa-solid fa-lightbulb"></i> Suggestions</div>
      <div class="stack-sm">
        <a href="index.php?page=feed" class="link-card">
          <strong>Go to feed</strong>
          <span>Catch up with the latest posts from your network.</span>
        </a>
        <a href="index.php?page=chat" class="link-card">
          <strong>Open messages</strong>
          <span>Jump back into your conversations the moment a request is accepted.</span>
        </a>
      </div>
    </div>
  </aside>
</div>

<?php include BASE_PATH . '/app/views/partials/mobile_nav.php'; ?>
<?php require BASE_PATH . '/app/views/partials/footer.php'; ?>