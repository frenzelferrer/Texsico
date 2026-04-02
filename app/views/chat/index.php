<?php
$page = 'chat';
require BASE_PATH . '/app/views/partials/header.php';

function avatarUrlC($img, $name)
{
  if (!$img || $img === 'default.png') {
    return default_avatar_data_uri($name, 128);
  }

  $avatarPath = BASE_PATH . '/assets/uploads/avatars/' . $img;
  if (!is_file($avatarPath)) {
    return default_avatar_data_uri($name, 128);
  }

  return 'index.php?asset=avatar&f=' . urlencode($img);
}

function timeFmt($dt)
{
  return (new DateTime($dt))->format('g:i A');
}

function chatText($text)
{
  return html_entity_decode((string)$text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function formatVoiceDuration($seconds): string
{
  $seconds = (int)$seconds;
  if ($seconds <= 0) return '0:00';
  $mins = floor($seconds / 60);
  $secs = $seconds % 60;
  return $mins . ':' . str_pad((string)$secs, 2, '0', STR_PAD_LEFT);
}

function replyPreviewSnippet(array $message): string
{
  $type = (string)($message['reply_message_type'] ?? $message['message_type'] ?? 'text');
  if ($type === 'image') return 'Photo';
  if ($type === 'voice') return 'Voice message';
  $text = trim(chatText((string)($message['reply_message'] ?? $message['message'] ?? '')));
  if ($text === '') return 'Message';
  return app_strlen($text) > 72 ? app_substr($text, 0, 72) . '...' : $text;
}

function replyPreviewSender(array $message, int $currentUserId): string
{
  $senderId = (int)($message['reply_sender_id'] ?? $message['sender_id'] ?? 0);
  if ($senderId === $currentUserId) {
    return 'You';
  }
  $name = trim((string)($message['reply_sender_name'] ?? $message['sender_name'] ?? ''));
  return $name !== '' ? $name : 'this message';
}

function replyContextLabel(array $message, int $currentUserId, bool $isCurrentMessageMine): string
{
  $otherParty = replyPreviewSender($message, $currentUserId);
  if ($isCurrentMessageMine) {
    return 'You replied to ' . $otherParty;
  }

  $senderName = trim((string)($message['sender_name'] ?? ''));
  if ($senderName === '') {
    $senderName = 'They';
  }

  return $senderName . ' replied to ' . $otherParty;
}

$chatStateClass = $otherUser ? 'has-chat-selected' : 'show-list';
?>

<style>
  .app-layout { padding-top: var(--nav-h); }
  .chat-outer { display: flex; height: calc(var(--vvh, 100vh) - var(--nav-h)); min-height: calc(var(--vvh, 100vh) - var(--nav-h)); overflow: hidden; gap: 18px; padding: 18px; }
  .chat-sidebar-panel, .chat-content-wrap, .chat-main-panel, .chat-info-panel, .chat-main-header, .chat-input-area, .conv-item, .msg-row, .msg-bubble-content, .bubble, .chat-empty-state { min-width: 0; }
  .chat-sidebar-panel { width: 320px; flex-shrink: 0; display: flex; flex-direction: column; overflow: hidden; background: linear-gradient(180deg, rgba(255,255,255,0.11), rgba(255,255,255,0.04)); border: 1px solid rgba(255,255,255,0.12); border-radius: 28px; backdrop-filter: blur(26px) saturate(180%); box-shadow: 0 24px 80px rgba(0,0,0,0.24); }
  .chat-sidebar-panel-header { padding: 22px 20px 16px; border-bottom: 1px solid rgba(255,255,255,0.08); flex-shrink: 0; }
  .chat-sidebar-panel-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
  .chat-feed-link { display: inline-flex; align-items: center; gap: 8px; min-height: 40px; padding: 0 14px; border-radius: 999px; text-decoration: none; color: var(--text-muted); background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(14px); font-size: 13px; font-weight: 700; transition: transform .18s ease, border-color .18s ease, background .18s ease, color .18s ease; }
  .chat-feed-link:hover { color: var(--text); border-color: rgba(143,136,255,0.24); background: rgba(255,255,255,0.09); transform: translateY(-1px); }
  .chat-feed-link i { font-size: 13px; }
  .chat-sidebar-panel-title { font-family: var(--font-display); font-size: 30px; font-weight: 800; letter-spacing: -0.5px; display: flex; align-items: center; gap: 10px; }
  .chat-sidebar-panel-title i { color: var(--accent); font-size: 24px; }
  .chat-sidebar-panel-search { padding: 12px 16px 16px; border-bottom: 1px solid rgba(255,255,255,0.08); flex-shrink: 0; }
  .chat-sidebar-panel-search input { width: 100%; background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-pill); color: var(--text); padding: 11px 14px; font-size: 14px; outline: none; backdrop-filter: blur(16px); }
  .chat-sidebar-panel-search input:focus { border-color: var(--accent); box-shadow: var(--glow); }
  .conversations-list { flex: 1; overflow-y: auto; }
  .conv-item { display: flex; align-items: center; gap: 12px; padding: 14px 16px; text-decoration: none; color: var(--text); border-bottom: 1px solid rgba(255,255,255,0.06); transition: background 0.18s, border-color 0.18s, transform 0.18s; cursor: pointer; position: relative; }
  .conv-item:hover { background: rgba(255,255,255,0.07); transform: translateY(-1px); }
  .conv-item.active { background: linear-gradient(135deg, rgba(83,212,255,0.12), rgba(143,136,255,0.14)); border-left: 3px solid var(--accent4); padding-left: 13px; }
  .conv-avatar-wrap { position: relative; flex-shrink: 0; }
  .conv-item-info { flex: 1; min-width: 0; }
  .conv-item-name { font-weight: 600; font-size: 14px; color: var(--text); }
  .conv-item-preview { font-size: 12px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
  .conv-item-time { font-size: 11px; color: var(--text-dim); flex-shrink: 0; }
  .conv-unread-dot { position: absolute; top: -2px; right: -2px; width: 10px; height: 10px; background: var(--accent); border-radius: 50%; border: 2px solid var(--bg); }
  .new-chat-section { padding: 16px; border-top: 1px solid rgba(255,255,255,0.08); flex-shrink: 0; background: rgba(255,255,255,0.03); }
  .new-chat-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-dim); margin-bottom: 10px; }
  .all-users-list { display: flex; flex-direction: column; gap: 4px; max-height: 200px; overflow-y: auto; }
  .new-chat-user { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: var(--radius-sm); text-decoration: none; color: var(--text); font-size: 13px; transition: background 0.15s; }
  .new-chat-user:hover { background: rgba(255,255,255,0.08); }
  .chat-content-wrap { flex: 1; min-width: 0; display: block; }
  .chat-content-wrap.with-info { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 18px; }
  .chat-main-panel { flex: 1; height: 100%; display: flex; flex-direction: column; min-width: 0; background: linear-gradient(180deg, rgba(255,255,255,0.11), rgba(255,255,255,0.04)); border: 1px solid rgba(255,255,255,0.12); border-radius: 32px; backdrop-filter: blur(28px) saturate(180%); box-shadow: 0 24px 80px rgba(0,0,0,0.24); overflow: hidden; }
  .chat-info-panel { min-width: 0; height: 100%; display: flex; flex-direction: column; overflow: hidden; background: linear-gradient(180deg, rgba(255,255,255,0.11), rgba(255,255,255,0.04)); border: 1px solid rgba(255,255,255,0.12); border-radius: 32px; backdrop-filter: blur(28px) saturate(180%); box-shadow: 0 24px 80px rgba(0,0,0,0.24); }
  .chat-info-top { padding: 28px 20px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.08); background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0)); }
  .chat-info-avatar { width: 88px; height: 88px; border-radius: 50%; object-fit: cover; display: block; margin: 0 auto 12px; border: 3px solid rgba(83,212,255,0.42); box-shadow: 0 14px 34px rgba(83,212,255,0.16); }
  .chat-info-name { font-size: 18px; font-weight: 700; color: var(--text); }
  .chat-info-sub { font-size: 13px; color: var(--text-muted); margin-top: 4px; }
  .chat-info-meta { margin-top: 10px; font-size: 12px; color: var(--text-dim); }
  .chat-info-body { padding: 14px; display: flex; flex-direction: column; gap: 12px; overflow-y: auto; }
  .chat-info-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 14px; }
  .chat-info-section-title { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 12px; }
  .chat-info-actions { display: grid; gap: 10px; }
  .chat-info-action { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--text); padding: 12px 14px; border-radius: 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); transition: background 0.18s ease, border-color 0.18s ease, transform 0.18s ease; }
  .chat-info-action:hover { background: rgba(255,255,255,0.08); border-color: rgba(143,136,255,0.18); transform: translateY(-1px); }
  .chat-info-action i { width: 18px; text-align: center; color: var(--accent); }
  .chat-info-stats { display: grid; gap: 10px; }
  .chat-info-stat { display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: var(--text-muted); gap: 12px; }
  .chat-info-stat strong { color: var(--text); font-size: 14px; }
  .shared-photos-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
  .shared-photo { display: block; aspect-ratio: 1 / 1; border-radius: 14px; overflow: hidden; border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.04); }
  .shared-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .chat-info-empty { font-size: 13px; color: var(--text-dim); line-height: 1.45; }
  .chat-main-header { display: flex; align-items: center; gap: 12px; padding: 18px 22px; border-bottom: 1px solid rgba(255,255,255,0.08); flex-shrink: 0; background: rgba(255,255,255,0.05); }
.chat-back-link {
  display: inline-flex;
  width: 38px;
  height: 38px;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  text-decoration: none;
  border: 1px solid var(--border);
  color: var(--text-muted);
  background: var(--surface2);
  flex-shrink: 0;
}  .chat-back-link:hover { border-color: var(--accent); color: var(--text); }
  .chat-user-link { display: inline-flex; flex-shrink: 0; }
  .chat-user-meta { min-width: 0; }
  .chat-user-name { font-weight: 700; font-size: 15px; color: var(--text); line-height: 1.2; }
  .chat-user-username { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
  .chat-header-actions { margin-left: auto; display: flex; gap: 8px; flex-shrink: 0; }
  .chat-profile-label { display: inline; }
  .chat-info-toggle { display: none; width: 38px; height: 38px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.06); color: var(--text); align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: background 0.18s ease, border-color 0.18s ease, transform 0.18s ease; }
  .chat-info-toggle:hover { background: rgba(255,255,255,0.09); border-color: rgba(143,136,255,0.22); transform: translateY(-1px); }
  .chat-info-close { display: none; width: 38px; height: 38px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.06); color: var(--text); align-items: center; justify-content: center; cursor: pointer; margin-left: auto; margin-bottom: 14px; flex-shrink: 0; }
  .chat-info-overlay { display: none; }
  .chat-messages-area { flex: 1; overflow-y: auto; padding: 22px; display: flex; flex-direction: column; gap: 16px; background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0)), rgba(6, 11, 21, 0.22); }
  .msg-row { display: flex; align-items: flex-end; gap: 8px; width: 100%; }
  .msg-row.mine { flex-direction: row-reverse; justify-content: flex-start; }
  .msg-avatar { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; display: block; flex-shrink: 0; background: var(--surface2); border: 1px solid var(--border); }
  .msg-bubble-content { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; max-width: min(56%, 540px); min-width: 0; }
  .msg-row.mine .msg-bubble-content { align-items: flex-end; }
  .msg-inline-wrap { display: flex; align-items: flex-end; gap: 8px; max-width: 100%; min-width: 0; }
  .msg-row.mine .msg-inline-wrap { flex-direction: row-reverse; }
  .msg-main-stack { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; min-width: 0; max-width: 100%; }
  .msg-row.mine .msg-main-stack { align-items: flex-end; }
  .bubble { display: inline-block; width: fit-content; min-width: 50px; max-width: min(100%, 34rem); padding: 9px 12px; border-radius: 16px; font-size: 14px; line-height: 1.4; word-break: break-word; overflow-wrap: break-word; white-space: pre-wrap; }
  .bubble.theirs { background: rgba(255,255,255,0.09); border: 1px solid rgba(255,255,255,0.08); backdrop-filter: blur(16px); border-bottom-left-radius: 4px; }
  .bubble.mine { background: linear-gradient(135deg, rgba(83,212,255,0.96), rgba(143,136,255,0.96)); color: #fff; border-bottom-right-radius: 4px; box-shadow: 0 16px 34px rgba(83,212,255,0.2); }
  .bubble img { display: block; width: auto; height: auto; max-width: min(240px, 100%); max-height: 240px; border-radius: 12px; object-fit: cover; }
  .voice-bubble { min-width: 220px; }
  .voice-player { width: 100%; max-width: 260px; }
  .voice-duration { font-size: 11px; color: var(--text-dim); margin-top: 6px; }
  .bubble.mine .voice-duration { color: rgba(255,255,255,0.78); }
  .bubble-meta { font-size: 11px; color: var(--text-dim); padding: 0 4px; display:flex; align-items:center; gap:8px; }
  .msg-row.mine .bubble-meta { justify-content: flex-end; text-align: right; }
  .msg-tools { display:flex; align-items:center; gap:6px; padding: 0 2px; flex: 0 0 auto; opacity: 0; transform: translateY(3px); transition: opacity .18s ease, transform .18s ease; }
  .msg-row:hover .msg-tools, .msg-row:focus-within .msg-tools, .msg-row.is-tools-visible .msg-tools { opacity: 1; transform: translateY(0); }
  .msg-action-btn { width: 30px; height: 30px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.06); color: var(--text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: background .18s ease, border-color .18s ease, color .18s ease, transform .18s ease; }
  .msg-action-btn:hover { color: var(--text); border-color: rgba(143,136,255,0.24); background: rgba(255,255,255,0.1); transform: translateY(-1px); }
  .reply-context { display: flex; flex-direction: column; align-items: flex-start; gap: 6px; width: fit-content; max-width: min(100%, 22rem); margin-bottom: 6px; }
  .msg-row.mine .reply-context { align-items: flex-end; align-self: flex-end; }
  .reply-context-link, .reply-context-pill { border: none; padding: 0; margin: 0; font: inherit; appearance: none; -webkit-appearance: none; }
  .reply-context-link { display: inline-flex; align-items: center; gap: 6px; max-width: 100%; background: transparent; color: var(--text-muted); cursor: pointer; text-decoration: none; text-align: left; }
  .reply-context-link i { font-size: 11px; opacity: 0.92; }
  .reply-context-line { display: block; max-width: 100%; font-size: 12px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .reply-context-pill { display: inline-flex; align-items: center; max-width: min(100%, 18rem); min-height: 30px; padding: 6px 12px; border-radius: 999px; background: rgba(255,255,255,0.09); border: 1px solid rgba(255,255,255,0.08); color: rgba(232,238,252,0.88); cursor: pointer; text-align: left; }
  .reply-context-pill span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 12px; line-height: 1.25; }
  .msg-row.mine .reply-context-link { color: rgba(235,241,255,0.86); justify-content: flex-end; }
  .msg-row.mine .reply-context-line { text-align: right; }
  .msg-row.mine .reply-context-pill { background: rgba(255,255,255,0.14); color: rgba(255,255,255,0.94); justify-content: flex-end; text-align: right; }
  .read-receipt { display:inline-flex; align-items:center; gap:5px; color: var(--text-dim); font-size: 11px; }
  .read-receipt.seen { color: var(--accent4); }
  .chat-composer-stack { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px; }
  .chat-reply-bar { display:none; align-items:center; gap:8px; min-height:36px; padding:6px 10px; border-radius:14px; background: rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); }
  .chat-reply-bar.active { display:flex; }
  .chat-reply-accent { width: 3px; align-self: stretch; border-radius: 999px; background: linear-gradient(180deg, rgba(83,212,255,0.96), rgba(143,136,255,0.96)); }
  .chat-reply-copy { flex:1; min-width:0; display:flex; flex-direction:row; align-items:center; gap:8px; overflow:hidden; }
  .chat-reply-label { flex-shrink: 0; max-width: 48%; font-size: 11px; font-weight: 800; line-height: 1.2; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .chat-reply-preview { flex: 1; min-width: 0; padding-left: 8px; border-left: 1px solid rgba(255,255,255,0.08); font-size: 11px; line-height: 1.2; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .chat-reply-close { width: 26px; height: 26px; flex-shrink:0; border-radius:50%; border:1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: var(--text-muted); display:inline-flex; align-items:center; justify-content:center; cursor:pointer; }
  .chat-reply-close:hover { color: var(--text); border-color: rgba(143,136,255,0.24); background: rgba(255,255,255,0.08); }
  .msg-row.reply-focus .bubble { animation: replyPulse .9s ease; box-shadow: 0 0 0 2px rgba(83,212,255,0.3), 0 18px 40px rgba(83,212,255,0.12); }
  .chat-input-area { display: flex; align-items: flex-end; gap: 12px; padding: 16px 20px 18px; border-top: 1px solid rgba(255,255,255,0.08); background: linear-gradient(180deg, rgba(255,255,255,0.07), rgba(255,255,255,0.04)); flex-shrink: 0; position: relative; transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease, background .22s ease; }
  .chat-input-area::before { content: ''; position: absolute; inset: 0; pointer-events: none; border-radius: inherit; background: linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0)); opacity: .72; }
  .chat-input-area.is-composing { background: linear-gradient(180deg, rgba(255,255,255,0.09), rgba(255,255,255,0.05)); box-shadow: 0 18px 50px rgba(0,0,0,0.2); }
  .chat-composer-tools, .chat-composer-main { position: relative; z-index: 1; min-width: 0; }
  .chat-composer-tools { display:flex; align-items:center; gap:8px; flex-shrink:0; }
  .chat-composer-main { flex: 1; min-width: 0; display: flex; align-items: flex-end; gap: 8px; padding: 4px; border-radius: 20px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); backdrop-filter: blur(12px) saturate(135%); transition: border-color .2s ease, box-shadow .2s ease, background .2s ease; }
  .chat-input-area:focus-within .chat-composer-main, .chat-input-area.is-composing .chat-composer-main { border-color: rgba(143,136,255,0.24); background: rgba(255,255,255,0.07); box-shadow: 0 0 0 1px rgba(143,136,255,0.1); transform: none; }
  .chat-emoji-btn { width: 42px; height: 42px; border-radius: 50%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.07); color: rgba(228,236,255,0.72); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; backdrop-filter: blur(10px); transition: transform .16s ease, border-color .16s ease, color .16s ease, background .16s ease; }
  .chat-emoji-btn:hover { border-color: rgba(143,136,255,0.24); color: var(--text); background: rgba(255,255,255,0.08); transform: none; }
  .emoji-picker { position:absolute; bottom: calc(100% + 10px); right: 88px; width: min(280px, calc(100vw - 36px)); padding: 14px; border-radius: 20px; background: linear-gradient(180deg, rgba(12,18,33,0.92), rgba(15,22,38,0.9)); border: 1px solid rgba(255,255,255,0.12); box-shadow: var(--shadow); display:none; grid-template-columns: repeat(7, 1fr); gap: 8px; z-index: 5; }
  .emoji-picker.open { display:grid; }
  .emoji-chip { border:none; background: rgba(255,255,255,0.06); border-radius: 12px; height: 34px; cursor:pointer; font-size: 18px; transition: transform .18s ease, background .18s ease; }
  .emoji-chip:hover { background: rgba(255,255,255,0.12); transform: translateY(-1px); }
  .chat-media-btn, .chat-record-btn { width: 42px; height: 42px; border-radius: 50%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.07); color: rgba(228,236,255,0.72); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; backdrop-filter: blur(10px); transition: transform .16s ease, border-color .16s ease, color .16s ease, background .16s ease; }
  .chat-media-btn:hover, .chat-record-btn:hover { border-color: rgba(143,136,255,0.24); color: var(--text); background: rgba(255,255,255,0.08); transform: none; }
  .chat-record-btn.recording { background: rgba(255,107,107,0.16); color: #ff9f9f; border-color: rgba(255,107,107,0.4); box-shadow: 0 0 0 6px rgba(255,107,107,0.08); }
  .composer-avatar { transition: transform .22s ease, opacity .22s ease; }
  .chat-input-area.is-composing .composer-avatar { transform: scale(.92); opacity: .82; }
  .chat-text-input { flex: 1; min-width: 0; width: 100%; min-height: 40px; max-height: 120px; background: transparent; border: none; border-radius: 16px; color: var(--text); padding: 10px 12px; font-family: var(--font-body); font-size: 15px; line-height: 1.38; outline: none; transition: color 0.2s; resize: none; overflow-y: auto; scrollbar-width: none; }
  .chat-text-input::-webkit-scrollbar { display:none; }
  .chat-text-input::placeholder { color: rgba(218,226,244,0.58); }
  .chat-send-btn { border-radius: 50%; width: 48px; height: 48px; padding: 0; justify-content: center; flex-shrink: 0; align-self: flex-end; transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease; }
  .chat-send-btn:disabled { opacity: .56; box-shadow: none; cursor: not-allowed; }
  .chat-send-btn.is-ready { transform: translateY(-1px) scale(1.02); box-shadow: 0 20px 42px rgba(83,212,255,0.22); }
  .chat-attachment-state { display:none; align-items:center; gap:8px; padding:10px 14px; border-top:1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.04); font-size:12px; color:var(--text-muted); }
  .chat-attachment-state.active { display:flex; }
  .chat-attachment-pill { background: var(--surface2); border:1px solid var(--border); border-radius: var(--radius-pill); padding:6px 10px; }
  .chat-clear-attachment { background:none; border:none; color:var(--text-muted); cursor:pointer; }
  .chat-empty-state { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px; color: var(--text-muted); padding: 40px; text-align: center; background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0)); }
  .chat-empty-state { position: relative; }
  .chat-empty-feed-link { position: absolute; top: 22px; left: 22px; display: inline-flex; align-items: center; gap: 8px; min-height: 40px; padding: 0 14px; border-radius: 999px; text-decoration: none; color: var(--text-muted); background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(14px); font-size: 13px; font-weight: 700; transition: transform .18s ease, border-color .18s ease, background .18s ease, color .18s ease; }
  .chat-empty-feed-link:hover { color: var(--text); border-color: rgba(143,136,255,0.24); background: rgba(255,255,255,0.09); transform: translateY(-1px); }
  .chat-empty-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
  .chat-empty-emoji { font-size: 72px; opacity: 0.2; line-height: 1; }
  .chat-empty-title { font-size: 22px; font-weight: 700; font-family: var(--font-display); color: var(--text); }
  .chat-empty-copy { font-size: 15px; color: var(--text-muted); max-width: 280px; }
  .chat-start-state { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; color: var(--text-muted); text-align:center; padding: 28px 20px; background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0)); }
  .chat-start-avatar { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 3px solid var(--accent); }
  .chat-start-name { font-weight: 600; font-size: 16px; color: var(--text); }
  .chat-start-copy { font-size: 14px; }

  @keyframes replyPulse {
    0% { transform: translateY(0); }
    35% { transform: translateY(-2px); }
    100% { transform: translateY(0); }
  }

  html[data-theme="light"] .chat-sidebar-panel,
  html[data-theme="light"] .chat-main-panel,
  html[data-theme="light"] .chat-info-panel {
    background: linear-gradient(180deg, rgba(255,255,255,0.9), rgba(245,248,252,0.8));
    border-color: rgba(96,113,143,0.18);
    box-shadow: 0 24px 54px rgba(88, 102, 130, 0.12);
  }
  html[data-theme="light"] .chat-sidebar-panel-header,
  html[data-theme="light"] .chat-sidebar-panel-search,
  html[data-theme="light"] .new-chat-section,
  html[data-theme="light"] .chat-main-header,
  html[data-theme="light"] .chat-info-top {
    border-color: rgba(96,113,143,0.14);
    background: rgba(246,249,253,0.72);
  }
  html[data-theme="light"] .chat-sidebar-panel-search input,
  html[data-theme="light"] .chat-search-input-wrap,
  html[data-theme="light"] .chat-feed-link,
  html[data-theme="light"] .chat-empty-feed-link,
  html[data-theme="light"] .chat-back-link,
  html[data-theme="light"] .chat-info-toggle,
  html[data-theme="light"] .chat-info-close,
  html[data-theme="light"] .chat-info-card,
  html[data-theme="light"] .chat-info-action,
  html[data-theme="light"] .shared-photo,
  html[data-theme="light"] .new-chat-user,
  html[data-theme="light"] .chat-attachment-pill,
  html[data-theme="light"] .msg-action-btn,
  html[data-theme="light"] .chat-reply-bar,
  html[data-theme="light"] .chat-reply-close {
    background: rgba(242,246,252,0.92);
    border-color: rgba(96,113,143,0.18);
    color: #33415f;
  }
  html[data-theme="light"] .chat-feed-link:hover,
  html[data-theme="light"] .chat-empty-feed-link:hover,
  html[data-theme="light"] .chat-back-link:hover,
  html[data-theme="light"] .chat-info-toggle:hover,
  html[data-theme="light"] .chat-info-close:hover,
  html[data-theme="light"] .chat-info-action:hover,
  html[data-theme="light"] .new-chat-user:hover,
  html[data-theme="light"] .conv-item:hover {
    background: rgba(229,235,245,0.9);
    border-color: rgba(100,121,233,0.18);
  }
  html[data-theme="light"] .conv-item {
    border-bottom-color: rgba(96,113,143,0.1);
  }
  html[data-theme="light"] .conv-item.active {
    background: linear-gradient(135deg, rgba(100,121,233,0.12), rgba(59,174,215,0.12));
    border-left-color: var(--accent);
  }
  html[data-theme="light"] .chat-messages-area {
    background: linear-gradient(180deg, rgba(237,242,249,0.78), rgba(231,237,246,0.88));
  }
  html[data-theme="light"] .bubble.theirs {
    background: rgba(239,243,249,0.96);
    border-color: rgba(96,113,143,0.16);
    color: #1b2740;
  }
  html[data-theme="light"] .reply-context-link {
    color: #52627f;
  }
  html[data-theme="light"] .reply-context-pill {
    background: rgba(228,235,246,0.96);
    border-color: rgba(96,113,143,0.16);
    color: #33415f;
  }
  html[data-theme="light"] .msg-row.mine .reply-context-link {
    color: #52627f;
  }
  html[data-theme="light"] .msg-row.mine .reply-context-pill {
    background: rgba(233,240,255,0.94);
    border-color: rgba(96,113,143,0.14);
    color: #33415f;
  }
  html[data-theme="light"] .chat-reply-preview {
    color: rgba(78, 93, 121, 0.9);
  }
  html[data-theme="light"] .bubble-meta,
  html[data-theme="light"] .read-receipt,
  html[data-theme="light"] .voice-duration,
  html[data-theme="light"] .conv-item-time,
  html[data-theme="light"] .new-chat-title,
  html[data-theme="light"] .chat-info-empty,
  html[data-theme="light"] .chat-info-meta,
  html[data-theme="light"] .chat-info-sub,
  html[data-theme="light"] .conv-item-preview,
  html[data-theme="light"] .chat-user-username,
  html[data-theme="light"] .chat-start-copy,
  html[data-theme="light"] .chat-empty-copy {
    color: rgba(78, 93, 121, 0.86);
  }
  html[data-theme="light"] .chat-input-area {
    border-top-color: rgba(96,113,143,0.16);
    background: linear-gradient(180deg, rgba(250,252,255,0.94), rgba(241,246,252,0.9));
  }
  html[data-theme="light"] .chat-input-area::before {
    background: linear-gradient(180deg, rgba(255,255,255,0.7), rgba(255,255,255,0));
  }
  html[data-theme="light"] .chat-input-area.is-composing {
    background: linear-gradient(180deg, rgba(250,252,255,0.98), rgba(239,244,251,0.94));
    box-shadow: 0 16px 36px rgba(88, 102, 130, 0.12);
  }
  html[data-theme="light"] .chat-composer-main {
    background: rgba(248,250,254,0.96);
    border-color: rgba(96,113,143,0.18);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
  }
  html[data-theme="light"] .chat-input-area:focus-within .chat-composer-main,
  html[data-theme="light"] .chat-input-area.is-composing .chat-composer-main {
    background: rgba(252,253,255,0.98);
    border-color: rgba(100,121,233,0.3);
    box-shadow: 0 0 0 1px rgba(100,121,233,0.12);
  }
  html[data-theme="light"] .chat-media-btn,
  html[data-theme="light"] .chat-record-btn,
  html[data-theme="light"] .chat-emoji-btn {
    background: rgba(240,244,251,0.98);
    border-color: rgba(96,113,143,0.2);
    color: #5d6a86;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.65);
  }
  html[data-theme="light"] .chat-media-btn:hover,
  html[data-theme="light"] .chat-record-btn:hover,
  html[data-theme="light"] .chat-emoji-btn:hover {
    background: rgba(229,235,245,1);
    border-color: rgba(100,121,233,0.24);
    color: #33415f;
  }
  html[data-theme="light"] .chat-record-btn.recording {
    background: rgba(255,107,107,0.14);
    color: #c95757;
    border-color: rgba(214,98,98,0.32);
    box-shadow: 0 0 0 6px rgba(214,98,98,0.08);
  }
  html[data-theme="light"] .chat-text-input {
    color: #1b2740;
  }
  html[data-theme="light"] .chat-text-input::placeholder,
  html[data-theme="light"] .chat-sidebar-panel-search input::placeholder {
    color: rgba(90,104,132,0.82);
  }
  html[data-theme="light"] .emoji-picker {
    background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(242,246,252,0.98));
    border-color: rgba(96,113,143,0.18);
    box-shadow: 0 20px 40px rgba(88,102,130,0.14);
  }
  html[data-theme="light"] .emoji-chip {
    background: rgba(234,239,247,0.96);
  }
  html[data-theme="light"] .emoji-chip:hover {
    background: rgba(224,231,242,1);
  }
  html[data-theme="light"] .chat-attachment-state {
    background: rgba(245,248,252,0.92);
    border-top-color: rgba(96,113,143,0.14);
    color: #4b5b78;
  }
  html[data-theme="light"] .chat-clear-attachment {
    color: #4f5f7c;
  }


  @media (max-width: 1100px) {
    .chat-content-wrap.with-info { display:block; }
    .chat-info-panel { display:none; }
  }

  @media (max-width: 768px) {
    .chat-outer { display:block; height: calc(var(--vvh, 100vh) - var(--nav-h)); }
    .chat-content-wrap { width:100%; max-width:100%; height: calc(var(--vvh, 100vh) - var(--nav-h)); }
    .chat-sidebar-panel, .chat-main-panel { width:100%; max-width:100%; height: calc(var(--vvh, 100vh) - var(--nav-h)); }
    .chat-user-meta { flex: 1; min-width: 0; }
    .chat-user-name { font-size: clamp(16px, 4.8vw, 18px); line-height: 1.1; }
    .chat-user-username { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .chat-outer.show-list .chat-content-wrap { display:none; }
    .chat-outer.has-chat-selected .chat-sidebar-panel { display:none; }
    .chat-sidebar-panel { border-right:none; }
    .chat-sidebar-panel-header { padding:16px; }
    .chat-feed-link { min-width: 40px; padding: 0 12px; }
    .chat-feed-link span { display: none; }
    .chat-sidebar-panel-title { font-size:24px; }
    .chat-sidebar-panel-search { padding:10px 14px; }
    .conv-item { padding:13px 14px; }
    .conv-item.active { padding-left:11px; }
    .new-chat-section { padding:12px 14px 108px; }
    .chat-main-header { padding:12px 14px; gap:10px; }
    .chat-back-link { display:inline-flex; }
    .chat-header-actions .btn { padding:8px 12px; font-size:12px; }
    .chat-info-toggle { display:inline-flex; }
    .chat-info-overlay { display:block; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:1200; opacity:0; pointer-events:none; transition:opacity 0.24s ease; }
    .chat-info-overlay.open { opacity:1; pointer-events:auto; }
    .chat-info-panel { display:flex !important; position:fixed; top:calc(var(--nav-h) + 8px); right:8px; bottom:calc(76px + env(safe-area-inset-bottom, 0px) + 8px); width:min(88vw, 360px); height:auto; max-width:360px; border-radius:24px; z-index:1300; transform:translateX(calc(100% + 18px)); transition:transform 0.28s ease; overflow:hidden; }
    .chat-info-panel.open { transform:translateX(0); }
    .chat-info-top { padding:18px 16px 14px; }
    .chat-info-avatar { width:72px; height:72px; }
    .chat-info-close { display:inline-flex; }
    .chat-messages-area { padding:14px 14px 136px; gap:12px; }
    .msg-row { gap:6px; }
    .msg-avatar { width:32px; height:32px; }
    .msg-bubble-content { max-width: min(84%, 320px); }
    .bubble { font-size:13px; line-height:1.4; width: fit-content; max-width: 100%; padding:9px 12px; }
    .bubble img { max-width: min(180px, 100%); max-height:180px; }
    .voice-bubble { min-width: 180px; }
    .msg-tools { opacity: 1; transform: none; }
    .msg-action-btn { width: 32px; height: 32px; }
    .chat-reply-bar { width: 100%; min-height: 34px; padding: 5px 8px; border-radius: 13px; }
    .reply-context { max-width: min(100%, 17rem); }
    .reply-context-pill { max-width: min(100%, 14rem); }
    .chat-attachment-state {
      position: sticky;
      bottom: calc(76px + env(safe-area-inset-bottom, 0px));
      z-index: 8;
      margin: 0 10px;
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 16px 16px 0 0;
      backdrop-filter: blur(18px);
    }
    .chat-input-area {
      position: sticky;
      bottom: calc(76px + env(safe-area-inset-bottom, 0px));
      z-index: 9;
      margin: 0 8px 8px;
      padding: 8px 10px;
      gap: 8px;
      border: 1px solid rgba(255,255,255,0.06);
      border-radius: 18px;
      background: rgba(16, 24, 40, 0.72);
      backdrop-filter: blur(12px) saturate(140%);
      box-shadow: 0 8px 22px rgba(0,0,0,0.18);
      flex-direction: row;
      align-items: flex-end;
    }
    .chat-input-area::before {
      opacity: .38;
    }
    .chat-composer-tools {
      order: 1;
      width: auto;
      justify-content: flex-start;
      gap: 6px;
      overflow: visible;
      padding: 0;
      flex-shrink: 0;
    }
    .chat-composer-tools::-webkit-scrollbar { display:none; }
    .chat-input-area.is-composing .chat-composer-tools {
      gap: 6px;
    }
    .chat-composer-main {
      order: 2;
      flex: 1;
      width: auto;
      gap: 6px;
      padding: 4px;
      border-radius: 18px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.08);
      box-shadow: none;
    }
    .chat-input-area:focus-within .chat-composer-main,
    .chat-input-area.is-composing .chat-composer-main {
      transform: none;
      background: rgba(255,255,255,0.07);
      border-color: rgba(143,136,255,0.24);
      box-shadow: 0 0 0 1px rgba(143,136,255,0.1);
    }
    .chat-text-input { min-height:40px; max-height:96px; padding:10px 12px; font-size:16px; line-height:1.35; border-radius:14px; }
    .chat-send-btn, .chat-media-btn, .chat-record-btn, .chat-emoji-btn { width:38px; height:38px; }
    .chat-reply-label { max-width: 40%; }
    .chat-reply-preview { padding-left: 6px; }
    .chat-send-btn { width:42px; height:42px; }
    .composer-avatar { display: none; }
    .chat-empty-state { padding:24px 18px 126px; }
    .chat-empty-emoji { font-size:56px; }
    .chat-empty-title { font-size:18px; }
    .chat-empty-copy { font-size:14px; max-width:260px; }
    .chat-start-avatar { width:56px; height:56px; }

    .page-chat.chat-mobile-keyboard-open .mobile-nav {
      opacity: 0;
      pointer-events: none;
      transform: translateY(calc(100% + env(safe-area-inset-bottom, 0px)));
    }
    .page-chat.chat-mobile-keyboard-open .chat-messages-area {
      padding-bottom: 84px;
    }
    .page-chat.chat-mobile-keyboard-open .chat-empty-state {
      padding-bottom: 84px;
    }
    .page-chat.chat-mobile-keyboard-open .chat-attachment-state {
      bottom: max(6px, env(safe-area-inset-bottom, 0px));
    }
    .page-chat.chat-mobile-keyboard-open .chat-input-area {
      bottom: max(6px, env(safe-area-inset-bottom, 0px));
      margin-bottom: 6px;
    }
  }

  @media (max-width: 480px) {
    .chat-messages-area { padding: 12px 12px 144px; }
    .chat-composer-tools { gap: 6px; }
    .chat-sidebar-panel-header { gap: 8px; }
    .chat-sidebar-panel-title { font-size: 20px; }
    .chat-empty-state { justify-content: flex-start; padding-top: 86px; }
    .chat-empty-feed-link { top: 16px; left: 16px; }
    .chat-empty-actions { width: 100%; flex-direction: column; }
    .chat-empty-actions .btn { width: 100%; justify-content: center; }
    .chat-main-header {
      display: grid;
      grid-template-columns: auto auto minmax(0, 1fr) auto;
      align-items: center;
      gap: 8px;
      padding: 12px 56px 12px 12px;
    }
    .chat-user-link { grid-column: 2; }
    .chat-user-meta { grid-column: 3; }
    .chat-header-actions {
      grid-column: 4;
      margin-left: 0;
      gap: 6px;
    }
    .chat-info-toggle { width: 36px; height: 36px; }
    .chat-header-actions .btn { min-width: 0; padding: 8px 10px; }
    .chat-profile-label { display: none; }
    .chat-header-actions .btn i { margin: 0; }
    .chat-input-area.is-composing .chat-media-btn,
    .chat-input-area.is-composing .chat-record-btn,
    .chat-input-area.is-composing .chat-emoji-btn { transform: scale(.94); opacity: .92; }
  }

</style>

<div class="chat-outer <?= $chatStateClass ?>">
  <div class="chat-sidebar-panel">
    <div class="chat-sidebar-panel-header">
      <div class="chat-sidebar-panel-title"><i class="fa-regular fa-message"></i><span>Messages</span></div>
      <a href="index.php?page=feed" class="chat-feed-link" aria-label="Back to news feed">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Feed</span>
      </a>
    </div>

    <div class="chat-sidebar-panel-search">
      <input type="text" placeholder="Search conversations…" id="convSearch" oninput="filterConvs(this.value)">
    </div>

    <div class="conversations-list" id="convList">
      <?php if (!empty($conversations)): ?>
        <?php foreach ($conversations as $cv): ?>
          <a href="index.php?page=chat&with=<?= $cv['id'] ?>" class="conv-item <?= (isset($otherUser) && $otherUser && $otherUser['id'] == $cv['id']) ? 'active' : '' ?>" data-name="<?= htmlspecialchars(strtolower($cv['full_name'] . ' ' . $cv['username'])) ?>">
            <div class="conv-avatar-wrap">
              <img decoding="async" loading="lazy" src="<?= avatarUrlC($cv['profile_image'], $cv['full_name']) ?>" class="msg-avatar" alt="">
              <?php if ($cv['unread'] > 0): ?><span class="conv-unread-dot"></span><?php endif; ?>
            </div>
            <div class="conv-item-info">
              <div class="conv-item-name"><?= htmlspecialchars($cv['full_name']) ?></div>
              <div class="conv-item-preview"><?= htmlspecialchars(chatText($cv['last_message'] ?? '')) ?></div>
            </div>
            <?php if ($cv['last_time']): ?><div class="conv-item-time"><?= htmlspecialchars($cv['last_time']) ?></div><?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="padding:16px; color:var(--text-dim); font-size:13px;">No conversations yet.</div>
      <?php endif; ?>
    </div>

    <div class="new-chat-section">
      <div class="new-chat-title"><i class="fa-solid fa-plus"></i> New Chat</div>
      <div class="all-users-list">
        <?php foreach ($allUsers as $u): ?>
          <?php $alreadyInConv = false; foreach ($conversations as $cv) { if ($cv['id'] == $u['id']) { $alreadyInConv = true; break; } } ?>
          <?php if (!$alreadyInConv): ?>
            <a href="index.php?page=chat&with=<?= $u['id'] ?>" class="new-chat-user">
              <img decoding="async" loading="lazy" src="<?= avatarUrlC($u['profile_image'], $u['full_name']) ?>" style="width:28px;height:28px;border-radius:50%;object-fit:cover;" alt="">
              <span><?= htmlspecialchars($u['full_name']) ?></span>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if (empty($allUsers)): ?><div style="font-size:12px; color:var(--text-dim); padding:8px;">No friends available yet.</div><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="chat-content-wrap <?= ($otherUser && empty($chatLocked)) ? 'with-info' : 'single-pane' ?>">
    <div class="chat-main-panel">
    <?php if (!empty($chatLocked) && $otherUser): ?>
      <div class="chat-empty-state">
        <div class="chat-empty-emoji"><i class="fa-solid fa-user-lock"></i></div>
        <div class="chat-empty-title">Chat unlocks after friendship</div>
        <div class="chat-empty-copy">You need to be accepted as friends before you can see posts or messages with <?= htmlspecialchars($otherUser['full_name'] ?? "this user") ?>.</div>
        <div class="chat-empty-actions">
          <a href="index.php?page=profile&id=<?= (int)$otherUser['id'] ?>" class="btn btn-primary btn-sm"><i class="fa-regular fa-user"></i> Open profile</a>
        </div>
      </div>
    <?php elseif ($otherUser): ?>
      <div class="chat-main-header">
        <a href="index.php?page=chat" class="chat-back-link" aria-label="Back to conversations"><i class="fa-solid fa-arrow-left"></i></a>
        <a href="index.php?page=profile&id=<?= $otherUser['id'] ?>" class="chat-user-link">
          <img decoding="async" loading="lazy" src="<?= avatarUrlC($otherUser['profile_image'], $otherUser['full_name']) ?>" class="msg-avatar" alt="">
        </a>
        <div class="chat-user-meta">
          <div class="chat-user-name"><?= htmlspecialchars($otherUser['full_name']) ?></div>
          <div class="chat-user-username">@<?= htmlspecialchars($otherUser['username']) ?></div>
        </div>
        <div class="chat-header-actions">
          <button type="button" class="chat-info-toggle" id="chatInfoToggle" aria-label="Open chat info"><i class="fa-solid fa-circle-info"></i></button>
          <a href="index.php?page=profile&id=<?= $otherUser['id'] ?>" class="btn btn-ghost btn-sm" title="View profile"><i class="fa-regular fa-user"></i> <span class="chat-profile-label">Profile</span></a>
        </div>
      </div>

      <div class="chat-utility-bar">
        <div class="chat-search-input-wrap">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="chatSearchInput" class="chat-search-input" placeholder="Search this conversation">
        </div>
      </div>

      <div class="chat-messages-area" id="messagesArea">
        <?php if (empty($messages)): ?>
          <div class="chat-start-state">
            <img decoding="async" loading="lazy" src="<?= avatarUrlC($otherUser['profile_image'], $otherUser['full_name']) ?>" class="chat-start-avatar" alt="">
            <div class="chat-start-name"><?= htmlspecialchars($otherUser['full_name']) ?></div>
            <div class="chat-start-copy">Start with a quick hello, a voice note, or a photo. 👋</div>
          </div>
        <?php else: ?>
          <?php $lastMsgId = 0; foreach ($messages as $msg): $lastMsgId = max($lastMsgId, (int)$msg['id']); $isMine = ((int)$msg['sender_id'] === (int)$currentUserId); ?>
            <div class="msg-row <?= $isMine ? 'mine' : '' ?>" id="msg-<?= (int)$msg['id'] ?>"
                 data-message-id="<?= (int)$msg['id'] ?>"
                 data-message-type="<?= htmlspecialchars((string)($msg['message_type'] ?? 'text'), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                 data-message="<?= htmlspecialchars((string)($msg['message'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                 data-media-file="<?= htmlspecialchars((string)($msg['media_file'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                 data-media-duration="<?= (int)($msg['media_duration'] ?? 0) ?>"
                 data-sender-id="<?= (int)$msg['sender_id'] ?>"
                 data-sender-name="<?= htmlspecialchars((string)($isMine ? 'You' : ($otherUser['full_name'] ?? ($msg['sender_name'] ?? ''))), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>">
              <?php if (!$isMine): ?><img decoding="async" loading="lazy" src="<?= avatarUrlC($otherUser['profile_image'], $otherUser['full_name']) ?>" class="msg-avatar" alt=""><?php endif; ?>
              <div class="msg-bubble-content">
                <div class="msg-inline-wrap">
                  <div class="msg-main-stack">
                    <?php if (!empty($msg['reply_id'])): ?>
                      <div class="reply-context">
                        <button type="button" class="reply-context-link" data-reply-jump="<?= (int)$msg['reply_id'] ?>">
                          <i class="fa-solid fa-reply"></i>
                          <span class="reply-context-line"><?= htmlspecialchars(replyContextLabel($msg, (int)$currentUserId, $isMine), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></span>
                        </button>
                        <button type="button" class="reply-context-pill" data-reply-jump="<?= (int)$msg['reply_id'] ?>">
                          <span><?= htmlspecialchars(replyPreviewSnippet($msg), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></span>
                        </button>
                      </div>
                    <?php endif; ?>
                    <div class="bubble <?= $isMine ? 'mine' : 'theirs' ?> <?= (($msg['message_type'] ?? 'text') === 'voice') ? 'voice-bubble' : '' ?>"><?php if (($msg['message_type'] ?? 'text') === 'image' && !empty($msg['media_file'])): ?>
                        <img decoding="async" loading="lazy" class="js-lightbox-image" src="index.php?asset=chat&f=<?= urlencode($msg['media_file']) ?>" alt="Chat image">
                      <?php elseif (($msg['message_type'] ?? 'text') === 'voice' && !empty($msg['media_file'])): ?>
                        <audio class="voice-player" controls preload="metadata">
                          <source src="index.php?asset=voice&f=<?= urlencode($msg['media_file']) ?>">
                        </audio>
                        <div class="voice-duration">Voice message • <?= htmlspecialchars(formatVoiceDuration($msg['media_duration'] ?? 0)) ?></div>
                      <?php else: ?><?= nl2br(htmlspecialchars(chatText($msg['message']), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?><?php endif; ?></div>
                  </div>
                  <div class="msg-tools"><button type="button" class="msg-action-btn" data-reply-trigger aria-label="Reply to message"><i class="fa-solid fa-reply"></i></button></div>
                </div>
                <div class="bubble-meta"><?= htmlspecialchars($msg['time_formatted'] ?? timeFmt($msg['created_at'])) ?><?php if ($isMine): ?><span class="read-receipt <?= !empty($msg['is_read']) ? 'seen' : '' ?>" data-msg-id="<?= (int)$msg['id'] ?>"><i class="fa-solid <?= !empty($msg['is_read']) ? 'fa-check-double' : 'fa-check' ?>"></i><span><?= !empty($msg['is_read']) ? 'Seen' : 'Sent' ?></span></span><?php endif; ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
        <div id="msgBottom"></div>
      </div>

      <div id="attachmentState" class="chat-attachment-state">
        <span id="attachmentStateText" class="chat-attachment-pill">No attachment selected</span>
        <button type="button" id="clearAttachmentBtn" class="chat-clear-attachment" onclick="clearPendingAttachment()">Clear</button>
      </div>

      <div class="chat-input-area" id="chatInputArea">
        <div class="emoji-picker" id="emojiPicker">
          <?php foreach (['😀','😂','😍','🥹','😎','🔥','🎉','👏','🙌','🙏','💙','💜','✨','🚀','👍','👌','🤝','🤔','😭','😮','😅','😴','🥳','💯','🌊','🫶','📚','💻'] as $emoji): ?>
            <button type="button" class="emoji-chip" onclick="insertEmoji('<?= $emoji ?>')"><?= $emoji ?></button>
          <?php endforeach; ?>
        </div>
        <div class="chat-composer-tools">
          <img decoding="async" loading="lazy" src="<?= avatarUrlC($currentAvatar, $currentFullName) ?>" class="msg-avatar composer-avatar" alt="You">
          <label class="chat-media-btn" title="Send image"><i class="fa-regular fa-image"></i><input type="file" id="chatImageInput" accept="image/*" hidden></label>
        </div>
        <div class="chat-composer-main">
          <div class="chat-composer-stack">
            <div class="chat-reply-bar" id="composerReplyBar">
              <span class="chat-reply-accent" aria-hidden="true"></span>
              <div class="chat-reply-copy">
                <div class="chat-reply-label" id="composerReplyLabel">Replying to message</div>
                <div class="chat-reply-preview" id="composerReplyPreview">Message preview</div>
              </div>
              <button type="button" class="chat-reply-close" id="clearReplyBtn" aria-label="Cancel reply"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <textarea class="chat-text-input" id="msgInput" rows="1" data-autogrow="true" data-max-height="144" maxlength="1000" placeholder="Message <?= htmlspecialchars(explode(' ', trim($otherUser['full_name'] ?? ''))[0] ?? 'them', ENT_QUOTES, 'UTF-8') ?>…"></textarea>
          </div>
          <button class="btn btn-primary chat-send-btn" id="sendMsgBtn" type="button" onclick="sendMsg()" aria-label="Send message" disabled><i class="fa-solid fa-paper-plane"></i></button>
        </div>
      </div>

     
    <?php else: ?>
      <div class="chat-empty-state">
        <a href="index.php?page=feed" class="chat-empty-feed-link"><i class="fa-solid fa-arrow-left"></i><span>Back to Feed</span></a>
        <div class="chat-empty-emoji">💬</div>
        <div class="chat-empty-title">Your Messages</div>
        <div class="chat-empty-copy">Select a conversation from the left, or start a new chat with someone on Texsico.</div>
        <div class="chat-empty-actions">
          <a href="index.php?page=feed" class="btn btn-ghost"><i class="fa-solid fa-house"></i> News Feed</a>
          <a href="index.php?page=search" class="btn btn-primary"><i class="fa-solid fa-users"></i> Find People</a>
        </div>
      </div>
    <?php endif; ?>
    </div>

    <?php if ($otherUser && empty($chatLocked)): ?>
      <aside class="chat-info-panel" id="chatInfoPanel">
        <div class="chat-info-top">
          <button type="button" class="chat-info-close" id="chatInfoClose" aria-label="Close chat info"><i class="fa-solid fa-xmark"></i></button>
          <img decoding="async" loading="lazy" src="<?= avatarUrlC($otherUser['profile_image'], $otherUser['full_name']) ?>" class="chat-info-avatar" alt="">
          <div class="chat-info-name"><?= htmlspecialchars($otherUser['full_name']) ?></div>
          <div class="chat-info-sub">@<?= htmlspecialchars($otherUser['username']) ?></div>
          <div class="chat-info-meta"><?= (int)($chatStats['total_photos'] ?? 0) ?> shared photos • <?= (int)($chatStats['total_voice'] ?? 0) ?> voice notes</div>
        </div>

        <div class="chat-info-body">
          <div class="chat-info-card">
            <div class="chat-info-section-title">Quick actions</div>
            <div class="chat-info-actions">
              <a href="index.php?page=profile&id=<?= $otherUser['id'] ?>" class="chat-info-action">
                <i class="fa-regular fa-user"></i>
                <span>View profile</span>
              </a>
              <a href="index.php?page=chat" class="chat-info-action">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to chats</span>
              </a>
            </div>
          </div>

          <div class="chat-info-card">
            <div class="chat-info-section-title">Conversation details</div>
            <div class="chat-info-stats">
              <div class="chat-info-stat">
                <span>Total messages</span>
                <strong><?= (int)($chatStats['total_messages'] ?? 0) ?></strong>
              </div>
              <div class="chat-info-stat">
                <span>Shared photos</span>
                <strong><?= (int)($chatStats['total_photos'] ?? 0) ?></strong>
              </div>
              <div class="chat-info-stat">
                <span>Voice notes</span>
                <strong><?= (int)($chatStats['total_voice'] ?? 0) ?></strong>
              </div>
            </div>
          </div>

          <div class="chat-info-card">
            <div class="chat-info-section-title">Shared photos</div>
            <?php if (!empty($sharedImages)): ?>
              <div class="shared-photos-grid">
                <?php foreach ($sharedImages as $photo): ?>
                  <a class="shared-photo" href="index.php?asset=chat&f=<?= urlencode($photo['media_file']) ?>" target="_blank" rel="noopener">
                    <img decoding="async" loading="lazy" src="index.php?asset=chat&f=<?= urlencode($photo['media_file']) ?>" alt="Shared photo">
                  </a>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="chat-info-empty">No shared photos yet. Send an image here and it will show up in this panel.</div>
            <?php endif; ?>
          </div>
        </div>
      </aside>
    <?php endif; ?>
  </div>
  <?php if ($otherUser && empty($chatLocked)): ?>
    <div class="chat-info-overlay" id="chatInfoOverlay"></div>
  <?php endif; ?>
</div>

<script>
  function scrollBottom() {
    const area = document.getElementById('messagesArea');
    if (area) area.scrollTop = area.scrollHeight;
  }

  function decodeHtmlEntities(str) {
    const txt = document.createElement('textarea');
    txt.innerHTML = str;
    return txt.value;
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function formatDuration(seconds) {
    seconds = parseInt(seconds || 0, 10);
    if (seconds <= 0) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${String(secs).padStart(2, '0')}`;
  }

  function updateAttachmentState(text = '', active = false) {
    const state = document.getElementById('attachmentState');
    const label = document.getElementById('attachmentStateText');
    if (!state || !label) return;
    label.textContent = text || 'No attachment selected';
    state.classList.toggle('active', active);
  }

  scrollBottom();

  const receiverId = <?= $otherUser ? (int)$otherUser['id'] : 'null' ?>;
  const currentUserId = <?= (int)$currentUserId ?>;
  let lastMsgId = <?= isset($lastMsgId) ? (int)$lastMsgId : 0 ?>;
  const csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>';
  const myAvatar = '<?= addslashes(avatarUrlC($currentAvatar, $currentFullName)) ?>';
  const theirAvatar = <?= $otherUser ? "'" . addslashes(avatarUrlC($otherUser['profile_image'], $otherUser['full_name'])) . "'" : 'null' ?>;
  const imageInput = document.getElementById('chatImageInput');
  const recordBtn = document.getElementById('recordVoiceBtn');
  const msgInput = document.getElementById('msgInput');
  const sendMsgBtn = document.getElementById('sendMsgBtn');
  const chatInputArea = document.getElementById('chatInputArea');
  const emojiPicker = document.getElementById('emojiPicker');
  const emojiToggleBtn = document.getElementById('emojiToggleBtn');
  const chatSearchInput = document.getElementById('chatSearchInput');
  const composerReplyBar = document.getElementById('composerReplyBar');
  const composerReplyLabel = document.getElementById('composerReplyLabel');
  const composerReplyPreview = document.getElementById('composerReplyPreview');
  const clearReplyBtn = document.getElementById('clearReplyBtn');
  const chatDraftKey = receiverId ? `texsico_chat_draft_${receiverId}` : '';
  let pendingVoiceBlob = null;
  let pendingReply = null;
  let pollDelay = 1800;
  let pollTimer = null;
  let lastSeenUpTo = 0;
  let pendingVoiceDuration = 0;
  let mediaRecorder = null;
  let recordingChunks = [];
  let recordingStartedAt = 0;
  const chatInfoToggle = document.getElementById('chatInfoToggle');
  const chatInfoPanel = document.getElementById('chatInfoPanel');
  const chatInfoClose = document.getElementById('chatInfoClose');
  const chatInfoOverlay = document.getElementById('chatInfoOverlay');
  let mobileViewportBaseline = 0;
  let keyboardSyncTimer = null;

  function openChatInfo() {
    if (!chatInfoPanel || !chatInfoOverlay) return;
    chatInfoPanel.classList.add('open');
    chatInfoOverlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeChatInfo() {
    if (!chatInfoPanel || !chatInfoOverlay) return;
    chatInfoPanel.classList.remove('open');
    chatInfoOverlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  if (chatInfoToggle && chatInfoPanel && chatInfoOverlay) {
    chatInfoToggle.addEventListener('click', openChatInfo);
    chatInfoOverlay.addEventListener('click', closeChatInfo);
    chatInfoClose?.addEventListener('click', closeChatInfo);
    window.addEventListener('resize', () => {
      if (window.innerWidth > 768) closeChatInfo();
    });
  }

  function autoResizeComposer() {
    if (typeof autoGrowTextarea === 'function' && msgInput) {
      autoGrowTextarea(msgInput);
    }
  }

  function isChatMobileLayout() {
    return window.matchMedia('(max-width: 768px)').matches;
  }

  function getChatViewportHeight() {
    return window.visualViewport ? window.visualViewport.height : window.innerHeight;
  }

  function detectMobileKeyboardOpen() {
    if (!msgInput || !isChatMobileLayout()) return false;
    const currentViewportHeight = getChatViewportHeight();
    const activeElement = document.activeElement;
    const inputFocused = activeElement === msgInput;

    if (!inputFocused) {
      if (!mobileViewportBaseline || currentViewportHeight > mobileViewportBaseline) {
        mobileViewportBaseline = currentViewportHeight;
      }
      return false;
    }

    if (!mobileViewportBaseline || currentViewportHeight > mobileViewportBaseline) {
      mobileViewportBaseline = currentViewportHeight;
    }

    return (mobileViewportBaseline - currentViewportHeight) > 110;
  }

  function syncMobileKeyboardState() {
    clearTimeout(keyboardSyncTimer);
    keyboardSyncTimer = window.setTimeout(() => {
      const isOpen = detectMobileKeyboardOpen();
      document.body.classList.toggle('chat-mobile-keyboard-open', isOpen);
      if (isOpen) {
        requestAnimationFrame(scrollBottom);
      }
    }, 24);
  }

  function syncComposerState() {
    const hasText = !!(msgInput && msgInput.value.trim());
    const hasAttachment = !!(pendingVoiceBlob || (imageInput && imageInput.files && imageInput.files[0]));
    const isFocused = document.activeElement === msgInput;
    chatInputArea?.classList.toggle('is-composing', hasText || hasAttachment || isFocused || !!pendingReply);
    chatInputArea?.classList.toggle('has-text', hasText);
    if (sendMsgBtn) {
      sendMsgBtn.disabled = !(hasText || hasAttachment);
      sendMsgBtn.classList.toggle('is-ready', hasText || hasAttachment);
    }
    autoResizeComposer();
  }

  function messagePreview(payload) {
    if (!payload) return 'Message';
    const type = String(payload.message_type || 'text');
    if (type === 'image') return 'Photo';
    if (type === 'voice') return `Voice message${payload.media_duration ? ` • ${formatDuration(payload.media_duration)}` : ''}`;
    const raw = decodeHtmlEntities(String(payload.message || '')).replace(/\s+/g, ' ').trim();
    if (!raw) return 'Message';
    return raw.length > 72 ? `${raw.slice(0, 72)}...` : raw;
  }

  function replySenderName(payload) {
    if (!payload) return 'message';
    return parseInt(payload.sender_id || 0, 10) === currentUserId ? 'You' : (payload.sender_name || 'this message');
  }

  function shortDisplayName(name) {
    const raw = String(name || '').trim();
    if (!raw || raw === 'message' || raw === 'this message') return 'message';
    if (raw === 'You') return raw;
    const first = raw.split(/\s+/)[0] || raw;
    return first.length > 20 ? `${first.slice(0, 20)}...` : first;
  }

  function setPendingReply(payload) {
    pendingReply = payload || null;
    if (!composerReplyBar || !composerReplyLabel || !composerReplyPreview) {
      syncComposerState();
      return;
    }
    if (!pendingReply) {
      composerReplyBar.classList.remove('active');
      composerReplyLabel.textContent = 'Replying to message';
      composerReplyPreview.textContent = 'Message preview';
      syncComposerState();
      return;
    }
    composerReplyLabel.textContent = `Reply to ${shortDisplayName(replySenderName(pendingReply))}`;
    composerReplyPreview.textContent = messagePreview(pendingReply);
    composerReplyBar.classList.add('active');
    syncComposerState();
  }

  function getRowPayload(row) {
    if (!row) return null;
    return {
      id: parseInt(row.dataset.messageId || '0', 10) || 0,
      sender_id: parseInt(row.dataset.senderId || '0', 10) || 0,
      sender_name: row.dataset.senderName || '',
      message_type: row.dataset.messageType || 'text',
      message: row.dataset.message || '',
      media_file: row.dataset.mediaFile || '',
      media_duration: parseInt(row.dataset.mediaDuration || '0', 10) || 0,
    };
  }

  function jumpToMessage(messageId) {
    const id = parseInt(messageId || 0, 10);
    if (!id) return;
    const node = document.getElementById(`msg-${id}`);
    if (!node) return;
    node.scrollIntoView({ behavior: 'smooth', block: 'center' });
    node.classList.add('reply-focus', 'is-tools-visible');
    setTimeout(() => node.classList.remove('reply-focus', 'is-tools-visible'), 1400);
  }

  function replyContextText(replyPayload, isMine = false) {
    const who = replySenderName(replyPayload);
    return isMine ? `You replied to ${who}` : `Replying to ${who}`;
  }

  function buildReplyContextHtml(replyPayload, isMine = false) {
    if (!replyPayload || !replyPayload.id) return '';
    return `
      <div class="reply-context">
        <button type="button" class="reply-context-link" data-reply-jump="${replyPayload.id}">
          <i class="fa-solid fa-reply"></i>
          <span class="reply-context-line">${escapeHtml(replyContextText(replyPayload, isMine))}</span>
        </button>
        <button type="button" class="reply-context-pill" data-reply-jump="${replyPayload.id}">
          <span>${escapeHtml(messagePreview(replyPayload))}</span>
        </button>
      </div>`;
  }

  function persistChatDraft() {
    if (!chatDraftKey || !msgInput) return;
    localStorage.setItem(chatDraftKey, msgInput.value);
  }

  function restoreChatDraft() {
    if (!chatDraftKey || !msgInput) return;
    const saved = localStorage.getItem(chatDraftKey);
    if (saved && !msgInput.value.trim()) {
      msgInput.value = saved;
    }
  }

  function filterChatMessages(query = '') {
    const needle = query.trim().toLowerCase();
    document.querySelectorAll('.msg-row').forEach(row => {
      const text = (row.querySelector('.bubble')?.textContent || '').toLowerCase();
      const match = !needle || text.includes(needle);
      row.classList.toggle('is-search-hidden', !match);
      row.classList.toggle('is-search-match', !!needle && match);
    });
  }

  function useQuickReply(text) {
    if (!msgInput) return;
    const base = msgInput.value.trim();
    msgInput.value = base ? `${base}${base.endsWith(' ') ? '' : ' '}${text}` : text;
    msgInput.focus();
    persistChatDraft();
    syncComposerState();
  }

  function clearPendingAttachment() {
    pendingVoiceBlob = null;
    pendingVoiceDuration = 0;
    if (imageInput) imageInput.value = '';
    updateAttachmentState('', false);
    if (recordBtn) recordBtn.classList.remove('recording');
    if (msgInput) msgInput.placeholder = 'Message <?= htmlspecialchars(explode(' ', trim($otherUser['full_name'] ?? ''))[0] ?? 'them', ENT_QUOTES, 'UTF-8') ?>…';
    syncComposerState();
  }

  if (imageInput) {
    imageInput.addEventListener('change', () => {
      if (imageInput.files && imageInput.files[0]) {
        pendingVoiceBlob = null;
        pendingVoiceDuration = 0;
        updateAttachmentState(`Image ready: ${imageInput.files[0].name}`, true);
      } else {
        updateAttachmentState('', false);
      }
      syncComposerState();
    });
  }

async function toggleVoiceRecording() {
  if (!recordBtn) return;

  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    alert('This browser does not support microphone recording.');
    return;
  }

  if (typeof MediaRecorder === 'undefined') {
    alert('Voice recording is not supported in this browser.');
    return;
  }

  if (mediaRecorder && mediaRecorder.state === 'recording') {
    mediaRecorder.stop();
    return;
  }

  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    recordingChunks = [];
    recordingStartedAt = Date.now();

    let mimeType = '';
    if (MediaRecorder.isTypeSupported('audio/webm;codecs=opus')) {
      mimeType = 'audio/webm;codecs=opus';
    } else if (MediaRecorder.isTypeSupported('audio/webm')) {
      mimeType = 'audio/webm';
    } else if (MediaRecorder.isTypeSupported('audio/ogg;codecs=opus')) {
      mimeType = 'audio/ogg;codecs=opus';
    }

    mediaRecorder = mimeType ? new MediaRecorder(stream, { mimeType }) : new MediaRecorder(stream);

    mediaRecorder.addEventListener('dataavailable', event => {
      if (event.data && event.data.size > 0) {
        recordingChunks.push(event.data);
      }
    });

    mediaRecorder.addEventListener('stop', () => {
      const finalMime = mediaRecorder.mimeType || mimeType || 'audio/webm';
      pendingVoiceBlob = new Blob(recordingChunks, { type: finalMime });
      pendingVoiceDuration = Math.max(1, Math.round((Date.now() - recordingStartedAt) / 1000));

      recordBtn.classList.remove('recording');
      if (msgInput) msgInput.placeholder = 'Message <?= htmlspecialchars(explode(' ', trim($otherUser['full_name'] ?? ''))[0] ?? 'them', ENT_QUOTES, 'UTF-8') ?>…';
      updateAttachmentState(`Voice ready: ${formatDuration(pendingVoiceDuration)}`, true);
      syncComposerState();

      stream.getTracks().forEach(track => track.stop());
    });

    mediaRecorder.start();
    recordBtn.classList.add('recording');
    if (msgInput) msgInput.placeholder = 'Recording voice… tap mic again to stop';
    updateAttachmentState('Recording voice… tap mic again to stop', true);
    syncComposerState();

  } catch (err) {
    console.error('Voice recording failed', err);

    if (err.name === 'NotAllowedError') {
      alert('Microphone permission was blocked. Allow microphone access for this site, then reload the page.');
    } else if (err.name === 'NotFoundError') {
      alert('No microphone was found on this device.');
    } else if (err.name === 'NotReadableError') {
      alert('Your microphone is being used by another app or tab.');
    } else if (err.name === 'SecurityError') {
      alert('Microphone access requires HTTPS.');
    } else if (err.name === 'AbortError') {
      alert('Microphone access was interrupted. Try again.');
    } else {
      alert(`Voice recording failed: ${err.name || 'Unknown error'}`);
    }
  }
}

  if (recordBtn) {
    recordBtn.addEventListener('click', toggleVoiceRecording);
  }

  if (emojiToggleBtn) {
    emojiToggleBtn.addEventListener('click', () => emojiPicker?.classList.toggle('open'));
  }

  document.addEventListener('click', (event) => {
    if (emojiPicker && emojiToggleBtn && !emojiPicker.contains(event.target) && !emojiToggleBtn.contains(event.target)) {
      emojiPicker.classList.remove('open');
    }

    const replyBtn = event.target.closest('[data-reply-trigger]');
    if (replyBtn) {
      event.preventDefault();
      const row = replyBtn.closest('.msg-row');
      setPendingReply(getRowPayload(row));
      msgInput?.focus();
      return;
    }

    const replyJump = event.target.closest('[data-reply-jump]');
    if (replyJump) {
      event.preventDefault();
      jumpToMessage(replyJump.getAttribute('data-reply-jump'));
    }
  });

  clearReplyBtn?.addEventListener('click', () => setPendingReply(null));

  function insertEmoji(emoji) {
    if (!msgInput) return;
    const start = msgInput.selectionStart ?? msgInput.value.length;
    const end = msgInput.selectionEnd ?? msgInput.value.length;
    msgInput.value = msgInput.value.slice(0, start) + emoji + msgInput.value.slice(end);
    const caret = start + emoji.length;
    msgInput.focus();
    msgInput.setSelectionRange(caret, caret);
    emojiPicker?.classList.remove('open');
    syncComposerState();
  }

  if (msgInput) {
    msgInput.addEventListener('input', () => { syncComposerState(); persistChatDraft(); syncMobileKeyboardState(); });
    msgInput.addEventListener('focus', () => {
      syncComposerState();
      syncMobileKeyboardState();
      window.setTimeout(syncMobileKeyboardState, 120);
      window.setTimeout(scrollBottom, 140);
    });
    msgInput.addEventListener('blur', () => {
      window.setTimeout(syncComposerState, 30);
      window.setTimeout(syncMobileKeyboardState, 120);
    });
    msgInput.addEventListener('keydown', (event) => {
      const desktopSend = window.innerWidth > 768 || event.ctrlKey || event.metaKey;
      if (event.key === 'Enter' && !event.shiftKey && desktopSend) {
        event.preventDefault();
        sendMsg();
      }
    });
  }

  window.addEventListener('resize', () => { syncComposerState(); syncMobileKeyboardState(); }, { passive: true });
  window.addEventListener('pageshow', () => {
    emojiPicker?.classList.remove('open');
    syncComposerState();
    syncMobileKeyboardState();
  });
  if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', syncMobileKeyboardState, { passive: true });
    window.visualViewport.addEventListener('scroll', syncMobileKeyboardState, { passive: true });
  }
  restoreChatDraft();
  chatSearchInput?.addEventListener('input', () => filterChatMessages(chatSearchInput.value));
  mobileViewportBaseline = getChatViewportHeight();
  syncComposerState();
  syncMobileKeyboardState();

  function receiptMarkup(id, isRead) {
    return `<span class="read-receipt ${isRead ? 'seen' : ''}" data-msg-id="${id}"><i class="fa-solid ${isRead ? 'fa-check-double' : 'fa-check'}"></i><span>${isRead ? 'Seen' : 'Sent'}</span></span>`;
  }

  function markSeenUpTo(seenUpTo) {
    const seen = parseInt(seenUpTo || 0, 10);
    if (!seen || seen <= lastSeenUpTo) return;
    lastSeenUpTo = seen;
    document.querySelectorAll('.read-receipt[data-msg-id]').forEach(node => {
      const id = parseInt(node.getAttribute('data-msg-id') || '0', 10);
      if (id > 0 && id <= seen) {
        node.classList.add('seen');
        node.innerHTML = '<i class="fa-solid fa-check-double"></i><span>Seen</span>';
      }
    });
  }

  async function sendMsg() {
    if (!receiverId) return;

    const message = msgInput ? msgInput.value.trim() : '';
    const imageFile = imageInput && imageInput.files ? imageInput.files[0] : null;
    const voiceBlob = pendingVoiceBlob;

    if (!message && !imageFile && !voiceBlob) return;

    const formData = new FormData();
    formData.append('receiver_id', receiverId);
    formData.append('message', message);
    formData.append('csrf_token', csrfToken);
    if (pendingReply && pendingReply.id) {
      formData.append('reply_to_message_id', String(pendingReply.id));
    }

    if (imageFile) {
      formData.append('chat_image', imageFile, imageFile.name);
    }

    if (voiceBlob) {
      const ext = voiceBlob.type.includes('ogg') ? 'ogg' : (voiceBlob.type.includes('mp4') ? 'm4a' : 'webm');
      formData.append('chat_voice', voiceBlob, `voice.${ext}`);
      formData.append('voice_duration', String(pendingVoiceDuration || 0));
    }

    if (msgInput) msgInput.value = '';
    clearPendingAttachment();
    syncComposerState();

    try {
      const res = await fetch('index.php?page=chat.send', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken },
        cache: 'no-store',
        body: formData
      });

      const data = await res.json();
      if (!data.success) {
        if (data.error) alert(data.error);
        return;
      }

      lastMsgId = Math.max(lastMsgId, parseInt(data.id, 10) || 0);
      if (data.message_type === 'image') {
        appendImageMsg(data.media_file, true, data.time, data.id, myAvatar, !!data.is_read, data.reply || null);
      } else if (data.message_type === 'voice') {
        appendVoiceMsg(data.media_file, data.media_duration || 0, true, data.time, data.id, myAvatar, !!data.is_read, data.reply || null);
      } else {
        appendTextMsg(data.message, true, data.time, data.id, myAvatar, !!data.is_read, data.reply || null);
      }
      setPendingReply(null);
      if (chatDraftKey) localStorage.removeItem(chatDraftKey);
      scrollBottom();
      msgInput?.focus();
      syncComposerState();
    } catch (e) {
      console.error('Send message failed', e);
    }
  }

  function insertMessageRow(innerHtml, isMine, time, id, avatarSrc, isRead = false, messageData = null) {
    const area = document.getElementById('messagesArea');
    const bottom = document.getElementById('msgBottom');
    if (!area || !bottom) return;
    if (document.getElementById('msg-' + id)) return;

    const div = document.createElement('div');
    div.className = 'msg-row' + (isMine ? ' mine' : '');
    div.id = 'msg-' + id;
    const avatarImg = `<img decoding="async" loading="lazy" src="${avatarSrc}" class="msg-avatar" alt="">`;
    const senderName = isMine ? 'You' : (messageData?.sender_name || 'Friend');
    div.dataset.messageId = String(id);
    div.dataset.messageType = messageData?.message_type || 'text';
    div.dataset.message = messageData?.message || '';
    div.dataset.mediaFile = messageData?.media_file || '';
    div.dataset.mediaDuration = String(messageData?.media_duration || 0);
    div.dataset.senderId = String(messageData?.sender_id || 0);
    div.dataset.senderName = senderName;

    div.innerHTML = `
      ${!isMine ? avatarImg : ''}
      <div class="msg-bubble-content">
        <div class="msg-inline-wrap">
          <div class="msg-main-stack">
            ${innerHtml}
          </div>
          <div class="msg-tools"><button type="button" class="msg-action-btn" data-reply-trigger aria-label="Reply to message"><i class="fa-solid fa-reply"></i></button></div>
        </div>
        <div class="bubble-meta">${escapeHtml(time)}${isMine ? receiptMarkup(id, isRead) : ''}</div>
      </div>
    `;

    area.insertBefore(div, bottom);
  }

  function appendTextMsg(text, isMine, time, id, avatarSrc, isRead = false, reply = null) {
    const normalized = decodeHtmlEntities(String(text));
    const escapedText = escapeHtml(normalized).replace(/\n/g, '<br>');
    insertMessageRow(`${buildReplyContextHtml(reply, isMine)}<div class="bubble ${isMine ? 'mine' : 'theirs'}">${escapedText}</div>`, isMine, time, id, avatarSrc, isRead, {
      sender_id: isMine ? currentUserId : receiverId,
      sender_name: isMine ? 'You' : '<?= addslashes($otherUser['full_name'] ?? 'Friend') ?>',
      message_type: 'text',
      message: normalized,
      media_file: '',
      media_duration: 0,
    });
  }

  function appendImageMsg(filename, isMine, time, id, avatarSrc, isRead = false, reply = null) {
    const imageHtml = `${buildReplyContextHtml(reply, isMine)}<div class="bubble ${isMine ? 'mine' : 'theirs'}"><img decoding="async" loading="lazy" class="js-lightbox-image" src="index.php?asset=chat&f=${encodeURIComponent(filename)}" alt="Chat image"></div>`;
    insertMessageRow(imageHtml, isMine, time, id, avatarSrc, isRead, {
      sender_id: isMine ? currentUserId : receiverId,
      sender_name: isMine ? 'You' : '<?= addslashes($otherUser['full_name'] ?? 'Friend') ?>',
      message_type: 'image',
      message: '',
      media_file: filename,
      media_duration: 0,
    });
  }

  function appendVoiceMsg(filename, duration, isMine, time, id, avatarSrc, isRead = false, reply = null) {
    const voiceHtml = `
      ${buildReplyContextHtml(reply, isMine)}
      <div class="bubble ${isMine ? 'mine' : 'theirs'} voice-bubble">
        <audio class="voice-player" controls preload="metadata">
          <source src="index.php?asset=voice&f=${encodeURIComponent(filename)}">
        </audio>
        <div class="voice-duration">Voice message • ${escapeHtml(formatDuration(duration))}</div>
      </div>`;
    insertMessageRow(voiceHtml, isMine, time, id, avatarSrc, isRead, {
      sender_id: isMine ? currentUserId : receiverId,
      sender_name: isMine ? 'You' : '<?= addslashes($otherUser['full_name'] ?? 'Friend') ?>',
      message_type: 'voice',
      message: '',
      media_file: filename,
      media_duration: duration,
    });
  }

  <?php if ($otherUser && empty($chatLocked)): ?>
  async function pollLoop() {
    if (!receiverId) return;
    try {
      const res = await fetch(`index.php?page=chat.poll&with=${receiverId}&last_id=${lastMsgId}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken },
        cache: 'no-store'
      });
      const data = await res.json();
      let hasNew = false;

      if (data.messages && data.messages.length > 0) {
        data.messages.forEach(m => {
          if (document.getElementById('msg-' + m.id)) return;
          const isMine = (parseInt(m.sender_id, 10) === currentUserId);
          const avatar = isMine ? myAvatar : theirAvatar;
          const time = m.time_formatted || m.created_at;
          const replyPayload = m.reply_id ? {
            id: parseInt(m.reply_id, 10) || 0,
            sender_id: parseInt(m.reply_sender_id || 0, 10) || 0,
            sender_name: m.reply_sender_name || '',
            message_type: m.reply_message_type || 'text',
            message: m.reply_message || '',
            media_file: m.reply_media_file || '',
            media_duration: parseInt(m.reply_media_duration || 0, 10) || 0,
          } : null;
          lastMsgId = Math.max(lastMsgId, parseInt(m.id, 10) || 0);
          hasNew = true;

          if ((m.message_type || 'text') === 'image' && m.media_file) {
            appendImageMsg(m.media_file, isMine, time, m.id, avatar, !!m.is_read, replyPayload);
          } else if ((m.message_type || 'text') === 'voice' && m.media_file) {
            appendVoiceMsg(m.media_file, m.media_duration || 0, isMine, time, m.id, avatar, !!m.is_read, replyPayload);
          } else {
            appendTextMsg(m.message, isMine, time, m.id, avatar, !!m.is_read, replyPayload);
          }
        });
        scrollBottom();
      }

      if (data.seen_up_to) {
        markSeenUpTo(data.seen_up_to);
      }

      pollDelay = 1800;
    } catch (e) {
      console.error('Polling failed', e);
      pollDelay = 3500;
    } finally {
      if (!document.hidden && navigator.onLine !== false) {
        pollTimer = setTimeout(pollLoop, pollDelay);
      }
    }
  }
  markSeenUpTo(<?= !empty($messages) ? max(array_map(fn($m) => (!empty($m['is_read']) && (int)$m['sender_id'] === (int)$currentUserId) ? (int)$m['id'] : 0, $messages)) : 0 ?>);
  pollTimer = setTimeout(pollLoop, pollDelay);
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      if (pollTimer) clearTimeout(pollTimer);
      pollTimer = null;
      return;
    }
    if (!pollTimer) {
      pollDelay = 1800;
      pollLoop();
    }
  });
  window.addEventListener('online', () => {
    if (!pollTimer) {
      pollDelay = 1800;
      pollLoop();
    }
  });
  <?php endif; ?>

  function filterConvs(q) {
    const items = document.querySelectorAll('.conv-item');
    q = (q || '').toLowerCase();
    items.forEach(item => {
      const name = item.dataset.name || '';
      item.style.display = name.includes(q) ? '' : 'none';
    });
  }
</script>
