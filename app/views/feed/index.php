<?php
$page = 'feed';
require BASE_PATH . '/app/views/partials/header.php';

function avatarUrl($img, $name = 'User') {
    if (!$img || $img === 'default.png') {
        return default_avatar_data_uri($name, 128);
    }
    return 'index.php?asset=avatar&f=' . urlencode($img);
}

function postAvatarUrl($img, $name) {
    if (!$img || $img === 'default.png') {
        return default_avatar_data_uri($name, 128);
    }
    return 'index.php?asset=avatar&f=' . urlencode($img);
}

function timeAgo($datetime) {
    $now  = new DateTime();
    $ago  = new DateTime($datetime);
    $diff = $now->getTimestamp() - $ago->getTimestamp();
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    if ($diff < 604800) return floor($diff/86400) . 'd ago';
    return $ago->format('M j, Y');
}

$storyUsers = $storyUsers ?? [];
$suggestedUsers = $suggestedUsers ?? [];
$discoverUsers = $discoverUsers ?? [];
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$firstName = htmlspecialchars(explode(' ', trim($currentFullName))[0] ?? ($currentFullName ?: 'there'));
?>

<div class="app-layout">
  <aside class="sidebar">
    <a href="index.php?page=feed" class="sidebar-brand">
      <img src="apple-touch-icon.png" alt="Texsico logo" class="sidebar-brand-icon">
      <span class="sidebar-brand-text">Texsico</span>
    </a>
    <a href="index.php?page=profile" class="sidebar-profile">
      <img decoding="async" loading="lazy" src="<?= postAvatarUrl($currentAvatar, $currentFullName) ?>" alt="You" class="avatar avatar-sm">
      <div>
        <div class="sidebar-profile-name"><?= htmlspecialchars($currentFullName) ?></div>
        <div class="sidebar-profile-user">@<?= htmlspecialchars($currentUsername) ?></div>
      </div>
    </a>

    <div class="sidebar-divider"></div>

    <ul class="sidebar-menu">
      <li><a href="index.php?page=feed" class="active"><span class="menu-icon"><i class="fa-solid fa-house"></i></span> Home</a></li>
      <li><a href="index.php?page=profile"><span class="menu-icon"><i class="fa-regular fa-user"></i></span> My Profile</a></li>
      <li><a href="index.php?page=search"><span class="menu-icon"><i class="fa-solid fa-users"></i></span> People</a></li>
      <li>
        <a href="index.php?page=chat">
          <span class="menu-icon"><i class="fa-regular fa-message"></i></span> Messages
          <?php if (!empty($unreadMsgCount) && $unreadMsgCount > 0): ?>
            <span class="menu-badge"><?= (int)$unreadMsgCount ?></span>
          <?php endif; ?>
        </a>
      </li>
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
   
    <?php if (!empty($search)): ?>
      <div class="search-card" style="margin-bottom:16px; display:flex; align-items:center; gap:10px;">
        <span style="color:var(--text-muted); font-size:14px;">
          <i class="fa-solid fa-magnifying-glass"></i>
          Results for "<strong style="color:var(--text)"><?= htmlspecialchars($search) ?></strong>"
        </span>
        <a href="index.php?page=feed" style="color:var(--accent); font-size:13px; text-decoration:none;">Clear</a>
      </div>
    <?php endif; ?>

    <section class="card" style="padding:18px 20px;">
      <div class="feed-kicker"><i class="fa-solid fa-signal"></i> Friends</div>
      <div style="display:flex; gap:14px; overflow:auto; padding-top:10px;">
        <a href="index.php?page=profile" style="text-decoration:none; color:inherit; text-align:center; min-width:72px;">
          <div style="position:relative; width:64px; margin:0 auto 8px;">
            <img decoding="async" loading="lazy" src="<?= postAvatarUrl($currentAvatar, $currentFullName) ?>" alt="Your story" style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:2px solid rgba(83,212,255,.85); padding:3px; background:rgba(255,255,255,.05);">
            <span style="position:absolute; right:0; bottom:0; width:22px; height:22px; border-radius:50%; background:linear-gradient(135deg, var(--accent4), var(--accent)); color:#fff; display:flex; align-items:center; justify-content:center; font-size:11px; border:2px solid var(--surface);"><i class="fa-solid fa-plus"></i></span>
          </div>
          <div style="font-size:12px; color:var(--text-muted);">Your space</div>
        </a>
        <?php foreach ($storyUsers as $storyUser): ?>
          <a href="index.php?page=profile&id=<?= (int)$storyUser['id'] ?>" style="text-decoration:none; color:inherit; text-align:center; min-width:72px;">
            <div style="position:relative; width:64px; margin:0 auto 8px;">
              <img decoding="async" loading="lazy" src="<?= postAvatarUrl($storyUser['profile_image'], $storyUser['full_name']) ?>" alt="<?= htmlspecialchars($storyUser['full_name']) ?>" style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:2px solid rgba(143,136,255,.85); padding:3px; background:rgba(255,255,255,.05);">
              <span style="position:absolute; right:4px; bottom:4px; width:10px; height:10px; border-radius:50%; background:#34d399; border:2px solid var(--surface);"></span>
            </div>
            <div style="font-size:12px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars(explode(' ', trim($storyUser['full_name']))[0] ?? $storyUser['full_name']) ?></div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="card feed-hero">
      <div class="feed-hero-content">
        <div>
          <div class="feed-kicker"><i class="fa-solid fa-sparkles"></i> Your social desk</div>
          <h1 class="feed-title"><?= $greeting ?>, <span class="accent-text"><?= $firstName ?></span>.</h1>
          <p class="feed-subtitle">Here is what is moving on Texsico right now. Post updates, continue conversations, and keep your network warm without the extra noise.</p>
          <div class="feed-chips">
            <span class="feed-chip"><i class="fa-solid fa-pen-nib"></i> Share an update</span>
            <span class="feed-chip"><i class="fa-solid fa-users"></i> <?= count($storyUsers) + 1 ?> people in view</span>
            <span class="feed-chip"><i class="fa-solid fa-comments"></i> <?= (int)($unreadMsgCount ?? 0) ?> unread messages</span>
          </div>
        </div>
       
      </div>
    </section>

   

    <div class="card create-post-card" id="composerCard">
      <div class="create-post-inner">
        <img decoding="async" loading="lazy" src="<?= postAvatarUrl($currentAvatar, $currentFullName) ?>" alt="You" class="avatar avatar-sm" style="margin-top:4px;">
        <textarea class="create-post-textarea" id="newPostText" data-autogrow="true" data-max-height="220" placeholder="Share a clear update, <?= $firstName ?>..." rows="2" maxlength="1000"></textarea>
      </div>
  
      <div class="create-post-footer">
        <div class="composer-tools">
          <label class="file-label">
            <i class="fa-regular fa-image"></i> Photo
            <input type="file" id="newPostImage" accept="image/*">
          </label>
          <button type="button" class="tool-pill" onclick="applyComposerTemplate('mood')"><i class="fa-regular fa-face-smile"></i> Mood</button>
          <button type="button" class="tool-pill" onclick="applyComposerTemplate('vibe')"><i class="fa-solid fa-location-dot"></i> Vibes</button>
        </div>
        <div id="imagePreviewWrap" style="display:none; align-items:center; gap:8px; flex:1;">
          <img decoding="async" loading="lazy" id="imagePreview" src="" alt="" style="height:36px; width:36px; object-fit:cover; border-radius:10px; border:1px solid var(--border);">
          <span id="imageFileName" style="font-size:12px; color:var(--text-muted);"></span>
          <button onclick="clearImage()" style="background:none; border:none; color:var(--accent2); cursor:pointer; font-size:14px;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="charCount" style="font-size:12px; color:var(--text-dim); margin-left:auto;">0/1000</div>
        <button class="btn btn-primary btn-sm ms-auto" id="submitPostBtn" onclick="submitPost()">
          <i class="fa-solid fa-paper-plane"></i> Post
        </button>
      </div>
    </div>

    <div id="postsContainer">
      <?php if (empty($posts)): ?>
        <div class="card" style="padding:34px 24px; text-align:center; color:var(--text-muted);">
          <i class="fa-regular fa-compass" style="font-size:48px; display:block; margin-bottom:16px; opacity:0.34;"></i>
          <p style="font-size:22px; font-weight:700; color:var(--text);">Your feed is ready for its first post.</p>
          <p style="font-size:14px; margin:10px auto 18px; max-width:520px;">Complete your profile, message a few people, and publish your first update to make the space feel active.</p>
          <div style="display:flex; justify-content:center; gap:10px; flex-wrap:wrap; margin-bottom:18px;">
            <a href="index.php?page=profile" class="btn btn-ghost"><i class="fa-regular fa-user"></i> Complete profile</a>
            <button type="button" class="btn btn-primary" onclick="focusComposer()"><i class="fa-solid fa-pen"></i> Write first post</button>
          </div>
          <?php if (!empty($suggestedUsers)): ?>
            <div style="margin-top:10px;">
              <div class="widget-title" style="margin-bottom:12px;">People you may know</div>
              <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; text-align:left;">
                <?php foreach ($suggestedUsers as $u): ?>
                  <?php $sState = $u['friendship_state'] ?? 'none'; ?>
                  <div class="user-card" style="background:rgba(255,255,255,.04); border:1px solid var(--border); justify-content:space-between; gap:10px;">
                    <a href="index.php?page=profile&id=<?= (int)$u['id'] ?>" class="user-card-link">
                      <img decoding="async" loading="lazy" src="<?= postAvatarUrl($u['profile_image'], $u['full_name']) ?>" class="avatar avatar-sm" alt="">
                      <div>
                        <div class="user-card-name"><?= htmlspecialchars($u['full_name']) ?></div>
                        <div class="user-card-username">@<?= htmlspecialchars($u['username']) ?></div>
                      </div>
                    </a>
                    <?php if ($sState === 'accepted'): ?>
                      <a href="index.php?page=chat&with=<?= (int)$u['id'] ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-message"></i> Chat</a>
                    <?php else: ?>
                      <?= friend_action_button((int)$u['id'], $sState, true) ?>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <?php foreach ($posts as $post): ?>
          <?php
            $postComments = $comments[$post['id']] ?? [];
            $postText = trim((string)($post['content'] ?? ''));
            $postLen = function_exists('mb_strlen') ? mb_strlen($postText) : strlen($postText);
            $isDiscussion = (strpos($postText, '?') !== false) || ((int)$post['comment_count'] > 0);
          ?>
          <div class="card post-card" id="post-<?= $post['id'] ?>" data-filter-image="<?= $post['image'] ? '1' : '0' ?>" data-filter-discussion="<?= $isDiscussion ? '1' : '0' ?>" data-filter-short="<?= $postLen <= 140 ? '1' : '0' ?>">
            <div class="post-header">
              <a href="index.php?page=profile&id=<?= $post['user_id'] ?>">
                <img decoding="async" loading="lazy" src="<?= postAvatarUrl($post['profile_image'], $post['full_name']) ?>"
                     alt="<?= htmlspecialchars($post['full_name']) ?>"
                     class="avatar avatar-sm">
              </a>
              <div class="post-meta">
                <a href="index.php?page=profile&id=<?= $post['user_id'] ?>" class="post-author"><?= htmlspecialchars($post['full_name']) ?></a>
                <div class="post-username">@<?= htmlspecialchars($post['username']) ?></div>
                <div class="post-time"><i class="fa-regular fa-clock"></i> <?= timeAgo($post['created_at']) ?></div>
              </div>
              <?php if ($post['user_id'] == $currentUserId): ?>
              <div class="dropdown">
                <button class="btn btn-ghost btn-icon btn-sm" onclick="toggleDropdown(event, 'pd-<?= $post['id'] ?>')">
                  <i class="fa-solid fa-ellipsis"></i>
                </button>
                <div class="dropdown-menu" id="pd-<?= $post['id'] ?>">
                  <button class="dropdown-item" onclick="openEditPost(<?= $post['id'] ?>, <?= htmlspecialchars(json_encode($post['content'])) ?>)">
                    <i class="fa-solid fa-pen"></i> Edit
                  </button>
                  <button class="dropdown-item danger" onclick="deletePost(<?= $post['id'] ?>)">
                    <i class="fa-solid fa-trash"></i> Delete
                  </button>
                </div>
              </div>
              <?php endif; ?>
            </div>

            <div class="post-content" id="post-content-<?= $post['id'] ?>">
              <?= nl2br(htmlspecialchars($post['content'])) ?>
            </div>
          
            <?php if ($post['image']): ?>
              <img decoding="async" loading="lazy" src="index.php?asset=post&f=<?= urlencode($post['image']) ?>" alt="Post image" class="post-image js-lightbox-image">
            <?php endif; ?>

           
            <div class="post-actions">
              <button class="post-action-btn <?= $post['user_liked'] ? 'liked' : '' ?>"
                      id="like-btn-<?= $post['id'] ?>"
                      onclick="toggleLike(<?= $post['id'] ?>)">
                <i class="<?= $post['user_liked'] ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                <span id="like-count-<?= $post['id'] ?>"><?= $post['like_count'] ?></span>
              </button>
              <button class="post-action-btn" onclick="toggleComments(<?= $post['id'] ?>)">
                <i class="fa-regular fa-comment"></i>
                <span id="comment-count-<?= $post['id'] ?>"><?= $post['comment_count'] ?></span>
              </button>
              <?php if ($post['user_id'] != $currentUserId): ?>
              <a href="index.php?page=chat&with=<?= $post['user_id'] ?>" class="post-action-btn" style="text-decoration:none;">
                <i class="fa-regular fa-paper-plane"></i> Message
              </a>
              <?php endif; ?>
            </div>

            <div class="comments-section" id="comments-<?= $post['id'] ?>" style="display:none;">
              <div id="comments-list-<?= $post['id'] ?>">
                <?php foreach ($postComments as $c): ?>
                <div class="comment-item" id="comment-<?= $c['id'] ?>">
                  <img decoding="async" loading="lazy" src="<?= postAvatarUrl($c['profile_image'], $c['full_name']) ?>" alt="" class="avatar" style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                  <div class="comment-bubble" style="flex:1;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                      <span class="comment-author"><?= htmlspecialchars($c['full_name']) ?></span>
                      <?php if ($c['user_id'] == $currentUserId): ?>
                      <div class="comment-tools">
                        <button class="comment-tool" type="button" onclick="openEditComment(<?= $c['id'] ?>, <?= $post['id'] ?>, <?= htmlspecialchars(json_encode($c['content'])) ?>)"><i class="fa-solid fa-pen"></i></button>
                        <button class="comment-tool danger" type="button" onclick="deleteComment(<?= $c['id'] ?>, <?= $post['id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                      </div>
                      <?php endif; ?>
                    </div>
                    <div class="comment-text"><?= nl2br(htmlspecialchars($c['content'])) ?></div>
                    <div class="comment-time"><?= timeAgo($c['created_at']) ?></div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <div class="comment-form">
                <img decoding="async" loading="lazy" src="<?= postAvatarUrl($currentAvatar, $currentFullName) ?>" alt="You" class="avatar" style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                <input type="text" class="comment-input" id="comment-input-<?= $post['id'] ?>"
                       placeholder="Write a comment…"
                       onkeydown="if(event.key==='Enter'){submitComment(<?= $post['id'] ?>);}">
                <button class="btn btn-primary btn-sm" onclick="submitComment(<?= $post['id'] ?>)">
                  <i class="fa-solid fa-paper-plane"></i>
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>

  <aside class="right-sidebar">
    <div class="widget-title"><i class="fa-solid fa-user-plus"></i> Who to connect with</div>
    <?php foreach ($discoverUsers as $u): ?>
      <div class="user-card user-card-compact">
        <a href="index.php?page=profile&id=<?= $u['id'] ?>" class="user-card-link">
          <img decoding="async" loading="lazy" src="<?= postAvatarUrl($u['profile_image'], $u['full_name']) ?>" class="avatar avatar-sm" alt="">
          <div>
            <div class="user-card-name"><?= htmlspecialchars($u['full_name']) ?></div>
            <div class="user-card-username">@<?= htmlspecialchars($u['username']) ?></div>
          </div>
        </a>
        <?= friend_action_button((int)$u['id'], $u['friendship_state'] ?? 'none', true) ?>
      </div>
    <?php endforeach; ?>
    <div class="side-info-card">
      <div class="side-info-list">
<div class="side-info-item">
  <i class="fa-solid fa-earth-asia" style="color:var(--accent);"></i>
  <span>Posts are visible to all users.</span>
</div>        <div class="side-info-item"><i class="fa-solid fa-comments" style="color:var(--accent4);"></i><span>Chat opens after both users become friends.</span></div>
        <div class="side-info-item"><i class="fa-regular fa-bell" style="color:var(--accent2);"></i><span>Use the bell button on top for likes, comments, and requests.</span></div>
      </div>
    </div>
  </aside>
</div>

<button class="fab-post" type="button" onclick="focusComposer()">
  <i class="fa-solid fa-plus"></i> New post
</button>

<div class="modal-overlay" id="editPostModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Edit Post</span>
      <button class="modal-close" type="button" onclick="closeModal('editPostModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editPostId">
      <textarea class="form-control" id="editPostContent" rows="4" data-autogrow="true" data-max-height="240" placeholder="Edit your post…"></textarea>
      <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
        <button class="btn btn-ghost" type="button" onclick="closeModal('editPostModal')">Cancel</button>
        <button class="btn btn-primary" type="button" onclick="saveEditPost()"><i class="fa-solid fa-check"></i> Save</button>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="editCommentModal">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <span class="modal-title">Edit Comment</span>
      <button class="modal-close" type="button" onclick="closeModal('editCommentModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editCommentId">
      <input type="hidden" id="editCommentPostId">
      <textarea class="form-control" id="editCommentContent" rows="3" data-autogrow="true" data-max-height="200" placeholder="Edit your comment…"></textarea>
      <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
        <button class="btn btn-ghost" type="button" onclick="closeModal('editCommentModal')">Cancel</button>
        <button class="btn btn-primary" type="button" onclick="saveEditComment()"><i class="fa-solid fa-check"></i> Save</button>
      </div>
    </div>
  </div>
</div>

<script>
const CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
async function toggleLike(postId) {
  const res  = await fetch('index.php?page=post.like', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
    body: `post_id=${postId}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
  });
  const data = await res.json();
  const btn  = document.getElementById('like-btn-' + postId);
  const icon = btn.querySelector('i');
  document.getElementById('like-count-' + postId).textContent = data.count;
  if (data.liked) {
    btn.classList.add('liked');
    icon.className = 'fa-solid fa-heart';
  } else {
    btn.classList.remove('liked');
    icon.className = 'fa-regular fa-heart';
  }
  icon.style.transform = 'scale(1.4)';
  setTimeout(() => icon.style.transform = '', 200);
}

function toggleComments(postId) {
  const el = document.getElementById('comments-' + postId);
  if (el.style.display === 'none') {
    el.style.display = 'block';
    document.getElementById('comment-input-' + postId)?.focus();
  } else {
    el.style.display = 'none';
  }
}

async function submitComment(postId) {
  const input   = document.getElementById('comment-input-' + postId);
  const content = input.value.trim();
  if (!content) return;
  input.value = '';

  const res  = await fetch('index.php?page=comment.add', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
    body: `post_id=${postId}&content=${encodeURIComponent(content)}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
  });
  const data = await res.json();
  if (data.success) {
    const c = data.comment;
    const avatarUrl = c.profile_image && c.profile_image !== 'default.png'
      ? `index.php?asset=avatar&f=${encodeURIComponent(c.profile_image)}`
      : defaultAvatarUrl(c.full_name, 128);

    const html = `
      <div class="comment-item" id="comment-${c.id}">
        <img decoding="async" loading="lazy" src="${avatarUrl}" class="avatar" style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0;">
        <div class="comment-bubble" style="flex:1;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <span class="comment-author">${escHtml(c.full_name)}</span>
            <div class="comment-tools"><button class="comment-tool" type="button" data-comment-content="${escHtml(c.content)}" onclick="openEditComment(${c.id}, ${postId}, this.dataset.commentContent)"><i class="fa-solid fa-pen"></i></button><button class="comment-tool danger" type="button" onclick="deleteComment(${c.id}, ${postId})"><i class="fa-solid fa-trash"></i></button></div>
          </div>
          <div class="comment-text">${escHtml(c.content)}</div>
          <div class="comment-time">just now</div>
        </div>
      </div>`;
    document.getElementById('comments-list-' + postId).insertAdjacentHTML('beforeend', html);
    const cnt = document.getElementById('comment-count-' + postId);
    cnt.textContent = parseInt(cnt.textContent) + 1;
  }
}

async function deleteComment(commentId, postId) {
  const res  = await fetch('index.php?page=comment.delete', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
    body: `comment_id=${commentId}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
  });
  const data = await res.json();
  if (data.success) {
    document.getElementById('comment-' + commentId)?.remove();
    const cnt = document.getElementById('comment-count-' + postId);
    cnt.textContent = Math.max(0, parseInt(cnt.textContent) - 1);
    showToast('Comment deleted.', 'success');
  }
}

const newPostImg = document.getElementById('newPostImage');
const composerInput = document.getElementById('newPostText');
const charCount = document.getElementById('charCount');
const submitPostBtn = document.getElementById('submitPostBtn');
const composerDraftKey = 'texsico_feed_draft';
const feedFilterKey = 'texsico_feed_filter';
const feedDensityKey = 'texsico_feed_density';

function updateComposerCounter() {
  if (!composerInput || !charCount) return;
  const len = composerInput.value.length;
  charCount.textContent = `${len}/1000`;
  charCount.style.color = len > 950 ? '#ff8f8f' : (len > 800 ? '#f4b83a' : 'var(--text-dim)');
}

composerInput?.addEventListener('input', updateComposerCounter);

function setDraftStatus(message) {
  const el = document.getElementById('draftStatus');
  if (el) el.textContent = message;
}

function saveComposerDraft() {
  if (!composerInput) return;
  localStorage.setItem(composerDraftKey, composerInput.value);
  setDraftStatus(composerInput.value.trim() ? 'Draft saved locally.' : 'Draft autosave is on for this device.');
}

function restoreComposerDraft() {
  if (!composerInput) return;
  const saved = localStorage.getItem(composerDraftKey);
  if (saved && !composerInput.value.trim()) {
    composerInput.value = saved;
    setDraftStatus('Recovered your unsent draft.');
  } else {
    setDraftStatus('Draft autosave is on for this device.');
  }
  updateComposerCounter();
  if (typeof autoGrowTextarea === 'function') autoGrowTextarea(composerInput);
}

function applyComposerPrompt(text) {
  if (!composerInput) return;
  const spacer = composerInput.value && !composerInput.value.endsWith(' ') ? ' ' : '';
  composerInput.value = `${composerInput.value}${spacer}${text}`;
  composerInput.focus();
  updateComposerCounter();
  saveComposerDraft();
}

function setSegmentState(selector, activeValue, key) {
  document.querySelectorAll(selector).forEach(btn => {
    btn.classList.toggle('active', btn.dataset[key] === activeValue);
  });
}

function applyFeedFilter(mode = 'all') {
  document.querySelectorAll('#postsContainer .post-card').forEach(card => {
    let show = true;
    if (mode === 'photos') show = card.dataset.filterImage === '1';
    if (mode === 'discussions') show = card.dataset.filterDiscussion === '1';
    if (mode === 'short') show = card.dataset.filterShort === '1';
    card.classList.toggle('is-filtered', !show);
  });
  localStorage.setItem(feedFilterKey, mode);
  setSegmentState('#feedFilterBar .segment-btn', mode, 'feedFilter');
}

function applyFeedDensity(mode = 'comfortable') {
  document.body.classList.toggle('feed-compact', mode === 'compact');
  localStorage.setItem(feedDensityKey, mode);
  setSegmentState('#feedDensityBar .segment-btn', mode, 'feedDensity');
}

document.querySelectorAll('[data-feed-filter]').forEach(btn => {
  btn.addEventListener('click', () => applyFeedFilter(btn.dataset.feedFilter));
});

document.querySelectorAll('[data-feed-density]').forEach(btn => {
  btn.addEventListener('click', () => applyFeedDensity(btn.dataset.feedDensity));
});

composerInput?.addEventListener('input', saveComposerDraft);
restoreComposerDraft();
applyFeedFilter(localStorage.getItem(feedFilterKey) || 'all');
applyFeedDensity(localStorage.getItem(feedDensityKey) || 'comfortable');

updateComposerCounter();

function applyComposerTemplate(kind) {
  if (!composerInput) return;
  const templates = {
    mood: ['Shipping something new today ✨', 'Feeling focused and ready to build 💻', 'Taking notes, learning fast, and moving forward 📌'],
    vibe: ['#BuildLog', '#NeedFeedback', '#PhotoUpdate']
  };
  const list = templates[kind] || [];
  const choice = list[Math.floor(Math.random() * list.length)] || '';
  const spacer = composerInput.value && !composerInput.value.endsWith(' ') ? ' ' : '';
  composerInput.value = `${composerInput.value}${spacer}${choice} `;
  composerInput.focus();
  updateComposerCounter();
}

function renderComposerSkeleton() {
  const container = document.getElementById('postsContainer');
  if (!container || document.getElementById('feedSkeletonCard')) return;
  const skeleton = document.createElement('div');
  skeleton.className = 'card post-card';
  skeleton.id = 'feedSkeletonCard';
  skeleton.innerHTML = `
    <div style="padding:20px;">
      <div style="display:flex; gap:12px; align-items:center; margin-bottom:16px;">
        <div class="skeleton" style="width:40px; height:40px; border-radius:50%;"></div>
        <div style="flex:1;">
          <div class="skeleton" style="height:12px; width:140px; margin-bottom:8px;"></div>
          <div class="skeleton" style="height:10px; width:84px;"></div>
        </div>
      </div>
      <div class="skeleton" style="height:12px; width:100%; margin-bottom:10px;"></div>
      <div class="skeleton" style="height:12px; width:72%; margin-bottom:16px;"></div>
      <div class="skeleton" style="height:220px; width:100%; border-radius:18px;"></div>
    </div>`;
  container.prepend(skeleton);
}
newPostImg?.addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('imagePreview').src = e.target.result;
    document.getElementById('imageFileName').textContent = file.name;
    document.getElementById('imagePreviewWrap').style.display = 'flex';
  };
  reader.readAsDataURL(file);
});

function focusComposer() {
  const composer = document.getElementById('composerCard');
  const input = document.getElementById('newPostText');
  composer?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  setTimeout(() => input?.focus(), 250);
}

function burstTexsico() {
  const ring = document.createElement('div');
  ring.className = 'texsico-flash';
  document.body.appendChild(ring);
  setTimeout(() => ring.remove(), 950);
}

function clearImage() {
  document.getElementById('newPostImage').value = '';
  document.getElementById('imagePreviewWrap').style.display = 'none';
  document.getElementById('imagePreview').src = '';
  document.getElementById('imageFileName').textContent = '';
}

async function submitPost() {
  const content = document.getElementById('newPostText').value.trim();
  const imgFile = document.getElementById('newPostImage').files[0];

  if (!content && !imgFile) {
    showToast('Write something first!', 'error');
    return;
  }

  if (content.length > 1000) {
    showToast('Posts must stay within 1000 characters.', 'error');
    return;
  }

  const fd = new FormData();
  fd.append('content', content);
  fd.append('csrf_token', CSRF_TOKEN);
  if (imgFile) fd.append('image', imgFile);

  try {
    submitPostBtn?.setAttribute('disabled', 'disabled');
    renderComposerSkeleton();
    const res = await fetch('index.php?page=post.create', {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest'},
      body: fd
    });

    if (!res.ok) {
      const text = await res.text();
      console.error('Post failed:', text);
      showToast('Post failed. Check your post content.', 'error');
      document.getElementById('feedSkeletonCard')?.remove();
      submitPostBtn?.removeAttribute('disabled');
      return;
    }

    const data = await res.json();
    if (!data.success) {
      showToast(data.message || 'Post failed.', 'error');
      document.getElementById('feedSkeletonCard')?.remove();
      submitPostBtn?.removeAttribute('disabled');
      return;
    }

    burstTexsico();
    localStorage.removeItem(composerDraftKey);
    setDraftStatus('Draft cleared after publishing.');
    showToast('Post published.', 'success');
    setTimeout(() => location.reload(), 650);
  } catch (err) {
    console.error(err);
    document.getElementById('feedSkeletonCard')?.remove();
    submitPostBtn?.removeAttribute('disabled');
    showToast('Network error while posting.', 'error');
  }
}

function openEditPost(postId, content) {
  document.getElementById('editPostId').value = postId;
  document.getElementById('editPostContent').value = content;
  document.getElementById('editPostModal').classList.add('open');
}

async function saveEditPost() {
  const postId  = document.getElementById('editPostId').value;
  const content = document.getElementById('editPostContent').value.trim();
  if (!content) return;

  const res  = await fetch('index.php?page=post.update', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
    body: `post_id=${postId}&content=${encodeURIComponent(content)}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
  });
  const data = await res.json();
  if (data.success) {
    const el = document.getElementById('post-content-' + postId);
    if (el) el.innerHTML = escHtml(data.content).replace(/\n/g, '<br>');
    closeModal('editPostModal');
    showToast('Post updated!', 'success');
  }
}

async function deletePost(postId) {
  openConfirmModal('Delete this post? This cannot be undone.', async () => {
  const res  = await fetch('index.php?page=post.delete', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
    body: `post_id=${postId}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
  });
  const data = await res.json();
  if (data.success) {
    const el = document.getElementById('post-' + postId);
    el.style.opacity = '0'; el.style.transform = 'scale(0.95)';
    el.style.transition = 'all 0.3s';
    setTimeout(() => el.remove(), 300);
    showToast('Post deleted.', 'success');
  }
  }, 'Delete post', 'Delete');
}

function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
function toggleDropdown(event, id) {
  event.stopPropagation();
  document.querySelectorAll('.dropdown-menu.show').forEach(d => { if (d.id !== id) d.classList.remove('show'); });
  document.getElementById(id)?.classList.toggle('show');
}
document.addEventListener('click', () => document.querySelectorAll('.dropdown-menu.show').forEach(d => d.classList.remove('show')));
document.querySelectorAll('.modal-overlay').forEach(o => o.addEventListener('click', function(e) { if (e.target === this) closeModal(this.id); }));

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

function openEditComment(commentId, postId, content) {
  document.getElementById('editCommentId').value = commentId;
  document.getElementById('editCommentPostId').value = postId;
  document.getElementById('editCommentContent').value = content;
  document.getElementById('editCommentModal').classList.add('open');
}

async function saveEditComment() {
  const commentId = document.getElementById('editCommentId').value;
  const postId = document.getElementById('editCommentPostId').value;
  const content = document.getElementById('editCommentContent').value.trim();
  if (!content) return;

  const res = await fetch('index.php?page=comment.update', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
    body: `comment_id=${commentId}&content=${encodeURIComponent(content)}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
  });
  const data = await res.json();
  if (data.success && data.comment) {
    const textEl = document.querySelector(`#comment-${commentId} .comment-text`);
    if (textEl) textEl.innerHTML = escHtml(data.comment.content).replace(/\n/g, '<br>');
    closeModal('editCommentModal');
    showToast('Comment updated!', 'success');
    const commentsEl = document.getElementById('comments-' + postId);
    if (commentsEl && commentsEl.style.display === 'none') commentsEl.style.display = 'block';
  }
}

</script>
