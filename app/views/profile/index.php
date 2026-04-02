<?php
$page = 'profile';
require BASE_PATH . '/app/views/partials/header.php';

function timeAgoP($datetime) {
    $now  = new DateTime();
    $ago  = new DateTime($datetime);
    $diff = $now->getTimestamp() - $ago->getTimestamp();
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    if ($diff < 604800) return floor($diff/86400) . 'd ago';
    return $ago->format('M j, Y');
}

function avatarUrlP($img, $name) {
    if (!$img || $img === 'default.png') {
        return default_avatar_data_uri($name, 256);
    }
    return 'index.php?asset=avatar&f=' . urlencode($img);
}

function coverUrlP(?string $img): ?string {
    if (!$img) return null;
    return 'index.php?asset=cover&f=' . urlencode($img);
}

$isOwnProfile = ($profileUser['id'] == $currentUserId);
$profileChecklist = [
    'Username' => !empty($profileUser['username']),
    'Full name' => !empty($profileUser['full_name']),
    'First post' => count($posts) > 0,
    'Add bio' => !empty($profileUser['bio']),
    'Upload photo' => !empty($profileUser['profile_image']) && $profileUser['profile_image'] !== 'default.png',
    'Add cover' => !empty($profileUser['cover_photo']),
];
$profileCompletion = (int) round((array_sum(array_map(fn($v) => $v ? 1 : 0, $profileChecklist)) / count($profileChecklist)) * 100);
$coverImage = coverUrlP($profileUser['cover_photo'] ?? null);
?>

<div class="app-layout">
  <aside class="sidebar">
    <a href="index.php?page=feed" class="sidebar-brand">
      <img src="apple-touch-icon.png" alt="Texsico logo" class="sidebar-brand-icon">
      <span class="sidebar-brand-text">Texsico</span>
    </a>
    <a href="index.php?page=profile" class="sidebar-profile">
      <img decoding="async" loading="lazy" src="<?= avatarUrlP($currentAvatar, $currentFullName) ?>" alt="You" class="avatar avatar-sm">
      <div>
        <div class="sidebar-profile-name"><?= htmlspecialchars($currentFullName) ?></div>
        <div class="sidebar-profile-user">@<?= htmlspecialchars($currentUsername) ?></div>
      </div>
    </a>
    <div class="sidebar-divider"></div>
    <ul class="sidebar-menu">
      <li><a href="index.php?page=feed"><span class="menu-icon"><i class="fa-solid fa-house"></i></span> Home</a></li>
      <li><a href="index.php?page=profile" class="active"><span class="menu-icon"><i class="fa-regular fa-user"></i></span> My Profile</a></li>
      <li><a href="index.php?page=search"><span class="menu-icon"><i class="fa-solid fa-users"></i></span> Discover People</a></li>
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

  <main class="main-content page-shell" style="padding:0 0 40px;">
    <div class="card" style="border-radius:0; border-left:none; border-right:none; border-top:none; overflow:visible;">
      <div class="profile-cover"<?= $coverImage ? ' style="background-image:url(' . htmlspecialchars($coverImage, ENT_QUOTES, 'UTF-8') . '); background-size:cover; background-position:center;"' : '' ?>>
        <div class="profile-cover-overlay"></div>
        <?php if (!$coverImage): ?>
        <svg style="position:absolute;inset:0;width:100%;height:100%;opacity:0.15" preserveAspectRatio="none">
          <defs>
            <pattern id="dots" x="0" y="0" width="30" height="30" patternUnits="userSpaceOnUse">
              <circle cx="2" cy="2" r="1.5" fill="white"/>
            </pattern>
          </defs>
          <rect width="100%" height="100%" fill="url(#dots)"/>
        </svg>
        <?php endif; ?>
        <?php if ($isOwnProfile): ?>
          <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('editProfileModal').classList.add('open')" style="position:absolute; right:16px; bottom:16px; z-index:2;">
            <i class="fa-solid fa-camera"></i> <?= $coverImage ? 'Change cover' : 'Add cover' ?>
          </button>
        <?php endif; ?>
      </div>
      <div class="profile-header">
        <div class="profile-avatar-wrap">
          <img decoding="async" loading="lazy" src="<?= avatarUrlP($profileUser['profile_image'], $profileUser['full_name']) ?>"
               alt="<?= htmlspecialchars($profileUser['full_name']) ?>"
               class="avatar avatar-xl" style="border:4px solid var(--surface);">
        </div>
        <div class="profile-info" style="align-items:flex-start;">
          <div class="profile-details" style="flex:1;">
            <div class="profile-name"><?= htmlspecialchars($profileUser['full_name']) ?></div>
            <div class="profile-username">@<?= htmlspecialchars($profileUser['username']) ?></div>
            <?php if ($profileUser['bio']): ?>
              <div class="profile-bio"><?= nl2br(htmlspecialchars($profileUser['bio'])) ?></div>
            <?php endif; ?>
           
          <div style="display:flex; gap:8px; margin-top:8px; flex-wrap:wrap;">
            <?php if ($isOwnProfile): ?>
              <button class="btn btn-ghost btn-sm" onclick="document.getElementById('editProfileModal').classList.add('open')">
                <i class="fa-solid fa-pen"></i> Edit Profile
              </button>
            <?php else: ?>
              <?= friend_action_button((int)$profileUser['id'], $friendshipState ?? 'none', true) ?>
              <?php if (($friendshipState ?? 'none') === 'accepted'): ?>
                <a href="index.php?page=chat&with=<?= $profileUser['id'] ?>" class="btn btn-primary btn-sm">
                  <i class="fa-solid fa-message"></i> Message
                </a>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div style="padding: 24px;">
      <?php if ($isOwnProfile && $profileCompletion < 100): ?>
        <div class="card" style="padding:18px 20px; margin-bottom:18px;">
          <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:10px;">
            <div>
              <div class="widget-title" style="margin-bottom:6px;"><i class="fa-solid fa-chart-line"></i> Profile strength</div>
              <div style="font-size:24px; font-weight:800; font-family:var(--font-display);"><?= $profileCompletion ?>%</div>
            </div>
            <button class="btn btn-ghost btn-sm" type="button" onclick="document.getElementById('editProfileModal').classList.add('open')"><i class="fa-solid fa-pen"></i> Finish profile</button>
          </div>
          <div style="height:10px; border-radius:999px; background:rgba(255,255,255,.06); overflow:hidden; margin-bottom:14px;">
            <div style="height:100%; width:<?= $profileCompletion ?>%; background:linear-gradient(135deg, var(--accent4), var(--accent)); border-radius:999px;"></div>
          </div>
          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:8px 14px;">
            <?php foreach ($profileChecklist as $label => $done): ?>
              <div style="display:flex; align-items:center; gap:8px; color:<?= $done ? 'var(--text)' : 'var(--text-muted)' ?>; font-size:13px;">
                <i class="fa-solid <?= $done ? 'fa-circle-check' : 'fa-circle' ?>" style="color:<?= $done ? 'var(--accent4)' : 'rgba(255,255,255,.18)' ?>"></i>
                <span><?= htmlspecialchars($label) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
      <?php if (!$canViewPosts): ?>
        <div class="card locked-profile-card">
          <i class="fa-solid fa-lock"></i>
          <p><strong>Posts are private in their profile until you become friends.</strong></p>
          <span>Send a request first. After it is accepted, you can view posts inside their profile and start chatting here.</span>
        </div>
      <?php elseif (empty($posts)): ?>
        <div style="text-align:center; padding:60px; color:var(--text-muted);">
          <i class="fa-regular fa-newspaper" style="font-size:48px; display:block; margin-bottom:16px; opacity:0.3;"></i>
          <p style="font-size:18px; font-weight:600;">No posts yet</p>
          <?php if ($isOwnProfile): ?>
            <p style="margin-top:8px; font-size:14px;">Share something with the world!</p>
            <a href="index.php?page=feed" class="btn btn-primary" style="margin-top:16px; display:inline-flex;">
              <i class="fa-solid fa-pen-nib"></i> Create a Post
            </a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <?php foreach ($posts as $post): ?>
          <?php $postComments = $comments[$post['id']] ?? []; ?>
          <div class="card post-card" id="post-<?= $post['id'] ?>">
            <div class="post-header">
              <img decoding="async" loading="lazy" src="<?= avatarUrlP($profileUser['profile_image'], $profileUser['full_name']) ?>"
                   alt="<?= htmlspecialchars($profileUser['full_name']) ?>"
                   class="avatar avatar-sm">
              <div class="post-meta">
                <div class="post-author"><?= htmlspecialchars($profileUser['full_name']) ?></div>
                <div class="post-username">@<?= htmlspecialchars($profileUser['username']) ?></div>
                <div class="post-time"><i class="fa-regular fa-clock"></i> <?= timeAgoP($post['created_at']) ?></div>
              </div>
              <?php if ($isOwnProfile): ?>
              <div class="dropdown">
                <button class="btn btn-ghost btn-icon btn-sm" onclick="toggleDropdown(event, 'ppd-<?= $post['id'] ?>')">
                  <i class="fa-solid fa-ellipsis"></i>
                </button>
                <div class="dropdown-menu" id="ppd-<?= $post['id'] ?>">
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
            <div class="post-content" id="post-content-<?= $post['id'] ?>"><?= nl2br(htmlspecialchars($post['content'])) ?></div>
            <?php if ($post['image']): ?>
              <img decoding="async" loading="lazy" src="index.php?asset=post&f=<?= urlencode($post['image']) ?>" class="post-image js-lightbox-image" alt="">
            <?php endif; ?>
            <div class="post-actions">
              <button class="post-action-btn <?= $post['user_liked'] ? 'liked' : '' ?>"
                      id="like-btn-<?= $post['id'] ?>" onclick="toggleLike(<?= $post['id'] ?>)">
                <i class="<?= $post['user_liked'] ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                <span id="like-count-<?= $post['id'] ?>"><?= $post['like_count'] ?></span>
              </button>
              <button class="post-action-btn" onclick="toggleComments(<?= $post['id'] ?>)">
                <i class="fa-regular fa-comment"></i>
                <span id="comment-count-<?= $post['id'] ?>"><?= $post['comment_count'] ?></span>
              </button>
            </div>
            <div class="comments-section" id="comments-<?= $post['id'] ?>" style="display:none;">
              <div id="comments-list-<?= $post['id'] ?>">
                <?php foreach ($postComments as $c): ?>
                <div class="comment-item" id="comment-<?= $c['id'] ?>">
                  <img decoding="async" loading="lazy" src="<?= avatarUrlP($c['profile_image'], $c['full_name']) ?>" class="avatar" style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                  <div class="comment-bubble" style="flex:1;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                      <span class="comment-author"><?= htmlspecialchars($c['full_name']) ?></span>
                      <?php if ($c['user_id'] == $currentUserId): ?>
                      <div class="comment-tools">
                        <button class="comment-tool" type="button" onclick="openEditComment(<?= $c['id'] ?>, <?= $post['id'] ?>, <?= htmlspecialchars(json_encode($c['content'])) ?>)"><i class="fa-solid fa-pen"></i></button>
                        <button class="comment-tool danger" type="button" onclick="deleteComment(<?= $c['id'] ?>, <?= $post['id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                      </div>
                      <?php endif; ?>
                    </div>
                    <div class="comment-text"><?= nl2br(htmlspecialchars($c['content'])) ?></div>
                    <div class="comment-time"><?= timeAgoP($c['created_at']) ?></div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <div class="comment-form">
                <img decoding="async" loading="lazy" src="<?= avatarUrlP($currentAvatar, $currentFullName) ?>" class="avatar" style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0;">
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
    <div class="widget-title">Profile Info</div>
    <div class="side-info-card">
      <div class="side-info-list">
        <div class="side-info-item"><i class="fa-solid fa-at" style="color:var(--accent);width:16px;"></i><span><?= htmlspecialchars($profileUser['username']) ?></span></div>
        <div class="side-info-item"><i class="fa-regular fa-calendar" style="color:var(--accent4);width:16px;"></i><span>Joined <?= (new DateTime($profileUser['created_at']))->format('F Y') ?></span></div>
        <div class="side-info-item"><i class="fa-solid fa-user-group" style="color:var(--accent2);width:16px;"></i><span><?= (int)($friendCount ?? 0) ?> friend<?= (int)($friendCount ?? 0) !== 1 ? 's' : '' ?></span></div>
      </div>
    </div>
  </aside>
</div>

<?php if ($isOwnProfile): ?>
<div class="modal-overlay" id="editProfileModal">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <span class="modal-title">Edit Profile</span>
      <button class="modal-close" onclick="closeModal('editProfileModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <form action="index.php?page=profile" method="POST" enctype="multipart/form-data">
        <?= csrf_input() ?>
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:20px;">
          <img decoding="async" loading="lazy" src="<?= avatarUrlP($profileUser['profile_image'], $profileUser['full_name']) ?>"
               id="avatarPreview" class="avatar avatar-lg" alt="">
          <div>
            <label class="file-label" style="display:inline-flex; margin-bottom:6px;">
              <i class="fa-solid fa-camera"></i> Change Photo
              <input type="file" name="profile_image" accept="image/*" onchange="previewAvatar(this)">
            </label>
            <p style="font-size:12px; color:var(--text-dim); margin-top:4px;">JPG, PNG, GIF — max 5MB</p>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($profileUser['full_name']) ?>" maxlength="80" required>
          <div style="font-size:12px; color:var(--text-dim); margin-top:6px;">Name max 80 characters.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Bio</label>
          <textarea name="bio" class="form-control" rows="3" data-autogrow="true" data-max-height="180" maxlength="300" placeholder="Tell the world about yourself…"><?= htmlspecialchars($profileUser['bio'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Cover Photo</label>
          <input type="file" name="cover_photo" accept="image/*" class="form-control">
          <div style="font-size:12px; color:var(--text-dim); margin-top:6px;">Use a wide photo for the best result.</div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
          <button type="button" class="btn btn-ghost" onclick="closeModal('editProfileModal')">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal-overlay" id="editPostModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Edit Post</span>
      <button class="modal-close" onclick="closeModal('editPostModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editPostId">
      <textarea class="form-control" id="editPostContent" rows="4" data-autogrow="true" data-max-height="240"></textarea>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;">
        <button class="btn btn-ghost" onclick="closeModal('editPostModal')">Cancel</button>
        <button class="btn btn-primary" onclick="saveEditPost()"><i class="fa-solid fa-check"></i> Save</button>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>

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
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;">
        <button class="btn btn-ghost" type="button" onclick="closeModal('editCommentModal')">Cancel</button>
        <button class="btn btn-primary" type="button" onclick="saveEditComment()"><i class="fa-solid fa-check"></i> Save</button>
      </div>
    </div>
  </div>
</div>

<script>
const CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
function toggleDropdown(event, id) {
  event.stopPropagation();
  document.querySelectorAll('.dropdown-menu.show').forEach(d => { if(d.id!==id) d.classList.remove('show'); });
  document.getElementById(id)?.classList.toggle('show');
}
document.addEventListener('click', () => document.querySelectorAll('.dropdown-menu.show').forEach(d => d.classList.remove('show')));

function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const r = new FileReader();
    r.onload = e => document.getElementById('avatarPreview').src = e.target.result;
    r.readAsDataURL(input.files[0]);
  }
}

async function toggleLike(postId) {
  const res  = await fetch('index.php?page=post.like', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body:`post_id=${postId}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`});
  const data = await res.json();
  const btn  = document.getElementById('like-btn-'+postId);
  const icon = btn.querySelector('i');
  document.getElementById('like-count-'+postId).textContent = data.count;
  if (data.liked) { btn.classList.add('liked'); icon.className='fa-solid fa-heart'; }
  else            { btn.classList.remove('liked'); icon.className='fa-regular fa-heart'; }
}

function toggleComments(postId) {
  const el = document.getElementById('comments-'+postId);
  el.style.display = el.style.display==='none' ? 'block' : 'none';
}

function escHtml(str) { const d=document.createElement('div'); d.textContent=str; return d.innerHTML; }
function openEditComment(commentId, postId, content) { document.getElementById('editCommentId').value=commentId; document.getElementById('editCommentPostId').value=postId; document.getElementById('editCommentContent').value=content; document.getElementById('editCommentModal').classList.add('open'); }

async function submitComment(postId) {
  const input=document.getElementById('comment-input-'+postId);
  const content=input.value.trim(); if(!content) return; input.value='';
  const res=await fetch('index.php?page=comment.add',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body:`post_id=${postId}&content=${encodeURIComponent(content)}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`});
  const data=await res.json();
  if(data.success){
    const c=data.comment;
    const av=c.profile_image&&c.profile_image!=='default.png'?`index.php?asset=avatar&f=${encodeURIComponent(c.profile_image)}`:defaultAvatarUrl(c.full_name, 128);
    document.getElementById('comments-list-'+postId).insertAdjacentHTML('beforeend',`<div class="comment-item" id="comment-${c.id}"><img decoding="async" loading="lazy" src="${av}" class="avatar" style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0;"><div class="comment-bubble" style="flex:1;"><div style="display:flex;justify-content:space-between;"><span class="comment-author">${escHtml(c.full_name)}</span><div class="comment-tools"><button class="comment-tool" type="button" data-comment-content="${escHtml(c.content)}" onclick="openEditComment(${c.id}, ${postId}, this.dataset.commentContent)"><i class="fa-solid fa-pen"></i></button><button class="comment-tool danger" type="button" onclick="deleteComment(${c.id},${postId})"><i class="fa-solid fa-trash"></i></button></div></div><div class="comment-text">${escHtml(c.content)}</div><div class="comment-time">just now</div></div></div>`);
    const cnt=document.getElementById('comment-count-'+postId); cnt.textContent=parseInt(cnt.textContent)+1;
  }
}

async function deleteComment(commentId, postId) {
  const res=await fetch('index.php?page=comment.delete',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body:`comment_id=${commentId}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`});
  const data=await res.json();
  if(data.success){document.getElementById('comment-'+commentId)?.remove();const cnt=document.getElementById('comment-count-'+postId);cnt.textContent=Math.max(0,parseInt(cnt.textContent)-1);}
}

async function saveEditComment() {
  const commentId=document.getElementById('editCommentId').value;
  const postId=document.getElementById('editCommentPostId').value;
  const content=document.getElementById('editCommentContent').value.trim();
  if(!content) return;
  const res=await fetch('index.php?page=comment.update',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body:`comment_id=${commentId}&content=${encodeURIComponent(content)}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`});
  const data=await res.json();
  if(data.success&&data.comment){const textEl=document.querySelector(`#comment-${commentId} .comment-text`);if(textEl)textEl.innerHTML=escHtml(data.comment.content).replace(/\n/g,'<br>');closeModal('editCommentModal');}
}

function openEditPost(postId, content) {
  document.getElementById('editPostId').value=postId;
  document.getElementById('editPostContent').value=content;
  document.getElementById('editPostModal').classList.add('open');
}

async function saveEditPost() {
  const postId=document.getElementById('editPostId').value;
  const content=document.getElementById('editPostContent').value.trim(); if(!content) return;
  const res=await fetch('index.php?page=post.update',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body:`post_id=${postId}&content=${encodeURIComponent(content)}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`});
  const data=await res.json();
  if(data.success){const el=document.getElementById('post-content-'+postId);if(el)el.innerHTML=escHtml(data.content).replace(/\n/g,'<br>');closeModal('editPostModal');showToast('Post updated!','success');}
}

async function deletePost(postId) {
  openConfirmModal('Delete this post? This cannot be undone.', async () => {
  const res=await fetch('index.php?page=post.delete',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body:`post_id=${postId}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`});
  const data=await res.json();
  if(data.success){const el=document.getElementById('post-'+postId);el.style.opacity='0';el.style.transform='scale(0.95)';el.style.transition='all 0.3s';setTimeout(()=>el.remove(),300);showToast('Deleted.','success');}
  }, 'Delete post', 'Delete');
}
</script>
