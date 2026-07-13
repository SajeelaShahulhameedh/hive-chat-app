<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();
$user    = getCurrentUser();
$room_id = isset($_GET['room']) ? (int)$_GET['room'] : 0;

if (!$room_id) {
    header("Location: /hive/dashboard.php");
    exit();
}

// Get room info
$room_result = mysqli_query($conn, "SELECT * FROM rooms WHERE room_id=$room_id");
if (!$room_result || mysqli_num_rows($room_result) === 0) {
    header("Location: /hive/dashboard.php");
    exit();
}
$room = mysqli_fetch_assoc($room_result);

// Get room members
$members_result = mysqli_query($conn, "
    SELECT u.user_id, u.username, u.profile_pic, u.user_status, u.last_seen, rm.role
    FROM room_members rm
    JOIN users u ON rm.user_id = u.user_id
    WHERE rm.room_id = $room_id
    ORDER BY u.user_status='online' DESC, u.username ASC
");
$members = [];
while ($m = mysqli_fetch_assoc($members_result)) {
    $members[] = $m;
}

// Check if current user is admin
$is_admin = false;
foreach ($members as $m) {
    if ($m['user_id'] == $user['user_id'] && $m['role'] === 'admin') {
        $is_admin = true;
        break;
    }
}

// Update status
mysqli_query($conn, "UPDATE users SET user_status='online', last_seen=NOW() 
                     WHERE user_id={$user['user_id']}");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HIVE — <?= htmlspecialchars($room['room_name']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --amber:  #F5A623;
      --amber2: #E09010;
      --dark:   #0E0B1A;
      --navy:   #1A1430;
      --card:   #160F2A;
      --purple: #7F77DD;
      --soft:   #C29BF5;
      --green:  #22C55E;
      --muted:  #9490B0;
      --text:   #FFFFFF;
      --border: rgba(245,166,35,0.1);
    }

    body {
      font-family: 'Space Grotesk', sans-serif;
      background: var(--dark);
      color: var(--text);
      height: 100vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    /* TOPBAR */
    .topbar {
      height: 64px;
      background: rgba(14,11,26,0.95);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      padding: 0 20px;
      gap: 14px;
      flex-shrink: 0;
    }

    .back-btn {
      color: var(--muted);
      text-decoration: none;
      font-size: 20px;
      display: flex;
      align-items: center;
      transition: color 0.2s;
    }

    .back-btn:hover { color: var(--amber); }

    .room-avatar-top {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      background: rgba(245,166,35,0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
      overflow: hidden;
    }

    .room-avatar-top img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .room-top-info { flex: 1; }

    .room-top-info h3 {
      font-family: 'Outfit', sans-serif;
      font-size: 16px;
      font-weight: 700;
    }

    .room-top-info p {
      font-size: 12px;
      color: var(--green);
    }

    .topbar-actions { display: flex; gap: 10px; align-items: center; }

    .topbar-btn {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 10px;
      padding: 7px 14px;
      font-size: 13px;
      color: var(--muted);
      cursor: pointer;
      text-decoration: none;
      transition: border-color 0.2s, color 0.2s;
      font-family: 'Space Grotesk', sans-serif;
    }

    .topbar-btn:hover { border-color: var(--amber); color: var(--amber); }

    /* LAYOUT */
    .chat-layout {
      flex: 1;
      display: flex;
      overflow: hidden;
    }

    /* SIDEBAR */
    .sidebar {
      width: 220px;
      background: var(--card);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
      overflow-y: auto;
    }

    .sidebar-label {
      font-size: 10px;
      letter-spacing: 3px;
      color: var(--amber);
      font-weight: 600;
      text-transform: uppercase;
      padding: 16px 16px 8px;
    }

    .member-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 16px;
      transition: background 0.15s;
    }

    .member-item:hover { background: rgba(255,255,255,0.03); }

    .member-av {
      position: relative;
      flex-shrink: 0;
    }

    .member-av img,
    .member-av .av-placeholder {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
    }

    .member-av .av-placeholder {
      background: var(--purple);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 700;
      color: #fff;
    }

    .status-dot {
      position: absolute;
      bottom: 0; right: 0;
      width: 9px; height: 9px;
      border-radius: 50%;
      border: 2px solid var(--card);
    }

    .status-dot.online  { background: var(--green); }
    .status-dot.away    { background: var(--amber); }
    .status-dot.busy    { background: #EF4444; }
    .status-dot.offline { background: var(--muted); }

    .member-name {
      font-size: 13px;
      color: var(--text);
      font-weight: 500;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .member-role {
      font-size: 10px;
      color: var(--amber);
      font-weight: 600;
    }

    /* CHAT AREA */
    .chat-area {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .messages-wrap {
      flex: 1;
      overflow-y: auto;
      padding: 24px 20px;
      display: flex;
      flex-direction: column;
      gap: 4px;
      scroll-behavior: smooth;
    }

    .messages-wrap::-webkit-scrollbar { width: 4px; }
    .messages-wrap::-webkit-scrollbar-track { background: transparent; }
    .messages-wrap::-webkit-scrollbar-thumb {
      background: rgba(245,166,35,0.2);
      border-radius: 4px;
    }

    /* DATE DIVIDER */
    .date-divider {
      text-align: center;
      margin: 12px 0;
      font-size: 11px;
      color: var(--muted);
      letter-spacing: 1px;
    }

    /* MESSAGE ROW */
    .msg-row {
      display: flex;
      gap: 10px;
      align-items: flex-end;
      margin-bottom: 2px;
    }

    .msg-row.mine { flex-direction: row-reverse; }

    .msg-row.consecutive .msg-av { visibility: hidden; }

    .msg-av {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
      flex-shrink: 0;
    }

    .msg-av-placeholder {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: var(--purple);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: 700;
      color: #fff;
      flex-shrink: 0;
    }

    .msg-content { max-width: 65%; }

    .msg-username {
      font-size: 11px;
      color: var(--muted);
      margin-bottom: 3px;
      padding-left: 4px;
    }

    .msg-row.mine .msg-username { display: none; }

    /* Reply preview */
    .reply-preview {
      background: rgba(255,255,255,0.05);
      border-left: 3px solid var(--amber);
      border-radius: 8px 8px 0 0;
      padding: 6px 10px;
      font-size: 12px;
      color: var(--muted);
      margin-bottom: -4px;
    }

    .reply-preview strong { color: var(--amber); font-size: 11px; }

    .msg-bubble {
      padding: 10px 14px;
      border-radius: 18px;
      font-size: 14px;
      line-height: 1.55;
      word-break: break-word;
      cursor: pointer;
      position: relative;
    }

    .msg-row:not(.mine) .msg-bubble {
      background: var(--navy);
      color: var(--text);
      border-bottom-left-radius: 4px;
    }

    .msg-row.mine .msg-bubble {
      background: var(--amber);
      color: var(--dark);
      border-bottom-right-radius: 4px;
      font-weight: 500;
    }

    .msg-bubble img {
      max-width: 240px;
      border-radius: 10px;
      display: block;
    }

    .msg-meta {
      display: flex;
      align-items: center;
      gap: 4px;
      margin-top: 3px;
      padding: 0 4px;
    }

    .msg-row:not(.mine) .msg-meta { justify-content: flex-start; }
    .msg-row.mine .msg-meta       { justify-content: flex-end; }

    .msg-time {
      font-size: 11px;
      color: var(--muted);
    }

    .msg-seen {
      font-size: 11px;
      color: var(--purple);
    }

    /* Reactions */
    .msg-reactions {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      margin-top: 4px;
    }

    .reaction-pill {
      background: rgba(255,255,255,0.07);
      border-radius: 50px;
      padding: 2px 8px;
      font-size: 13px;
      cursor: pointer;
    }

    /* Typing indicator */
    .typing-indicator {
      padding: 0 20px 8px;
      font-size: 12px;
      color: var(--muted);
      min-height: 24px;
      font-style: italic;
    }

    /* INPUT BAR */
    .input-bar {
      background: rgba(14,11,26,0.95);
      border-top: 1px solid var(--border);
      padding: 14px 20px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    /* Reply bar */
    .reply-bar {
      display: none;
      align-items: center;
      gap: 10px;
      background: rgba(245,166,35,0.08);
      border: 1px solid rgba(245,166,35,0.2);
      border-radius: 10px;
      padding: 8px 12px;
      font-size: 13px;
      color: var(--muted);
    }

    .reply-bar.active { display: flex; }
    .reply-bar strong { color: var(--amber); }

    .reply-close {
      margin-left: auto;
      cursor: pointer;
      color: var(--muted);
      font-size: 16px;
    }

    .reply-close:hover { color: var(--text); }

    .input-row {
      display: flex;
      gap: 10px;
      align-items: flex-end;
    }

    .input-actions {
      display: flex;
      gap: 6px;
      align-items: center;
    }

    .action-btn {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.08);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      cursor: pointer;
      transition: border-color 0.2s;
      flex-shrink: 0;
    }

    .action-btn:hover { border-color: var(--amber); }

    #msg-input {
      flex: 1;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 14px;
      padding: 12px 16px;
      font-family: 'Space Grotesk', sans-serif;
      font-size: 15px;
      color: var(--text);
      outline: none;
      resize: none;
      max-height: 120px;
      line-height: 1.5;
      transition: border-color 0.2s;
    }

    #msg-input:focus { border-color: var(--amber); }
    #msg-input::placeholder { color: var(--muted); }

    .send-btn {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: var(--amber);
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      flex-shrink: 0;
      transition: background 0.2s, transform 0.1s;
    }

    .send-btn:hover {
      background: var(--amber2);
      transform: scale(1.05);
    }

    .send-btn svg { fill: var(--dark); width: 18px; height: 18px; }

    /* Emoji picker */
    .emoji-picker {
      display: none;
      flex-wrap: wrap;
      gap: 8px;
      padding: 10px;
      background: var(--navy);
      border: 1px solid var(--border);
      border-radius: 14px;
    }

    .emoji-picker.open { display: flex; }
    .emoji-picker span {
      font-size: 22px;
      cursor: pointer;
      transition: transform 0.1s;
    }
    .emoji-picker span:hover { transform: scale(1.3); }

    /* Context menu */
    .ctx-menu {
      position: fixed;
      background: var(--navy);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 6px;
      z-index: 999;
      display: none;
      min-width: 160px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.4);
    }

    .ctx-menu.open { display: block; }

    .ctx-item {
      padding: 10px 14px;
      font-size: 14px;
      border-radius: 8px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: background 0.15s;
    }

    .ctx-item:hover { background: rgba(255,255,255,0.05); }
    .ctx-item.danger { color: #FCA5A5; }
  </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
  <a href="/hive/dashboard.php" class="back-btn">←</a>
  <div class="room-avatar-top">
    <?php
      $rpic = '/hive/assets/uploads/avatars/' . $room['room_pic'];
      if ($room['room_pic'] !== 'default_room.png' && file_exists($_SERVER['DOCUMENT_ROOT'] . $rpic)):
    ?>
      <img src="<?= $rpic ?>" alt="room"/>
    <?php else: ?>
      🐝
    <?php endif; ?>
  </div>
  <div class="room-top-info">
    <h3><?= htmlspecialchars($room['room_name']) ?></h3>
    <p id="member-status"><?= count($members) ?> members</p>
  </div>
  <div class="topbar-actions">
    <?php if ($is_admin): ?>
      <a href="/hive/group_settings.php?room=<?= $room_id ?>" class="topbar-btn">⚙️ Settings</a>
    <?php endif; ?>
    <a href="/hive/profile.php" class="topbar-btn">👤 Profile</a>
  </div>
</div>

<!-- LAYOUT -->
<div class="chat-layout">

  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="sidebar-label">Members</div>
    <?php foreach ($members as $m): ?>
      <div class="member-item">
        <div class="member-av">
          <?php
            $mpic = '/hive/assets/uploads/avatars/' . $m['profile_pic'];
            if ($m['profile_pic'] !== 'default_avatar.png' && file_exists($_SERVER['DOCUMENT_ROOT'] . $mpic)):
          ?>
            <img src="<?= $mpic ?>" alt="av" style="width:32px;height:32px;border-radius:50%;object-fit:cover;"/>
          <?php else: ?>
            <div class="av-placeholder"><?= strtoupper(substr($m['username'],0,1)) ?></div>
          <?php endif; ?>
          <span class="status-dot <?= $m['user_status'] ?>"></span>
        </div>
        <div>
          <div class="member-name"><?= htmlspecialchars($m['username']) ?></div>
          <?php if ($m['role'] === 'admin'): ?>
            <div class="member-role">Admin</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- CHAT AREA -->
  <div class="chat-area">
    <div class="messages-wrap" id="messages-wrap">
      <div class="date-divider">Today</div>
      <!-- Messages load here via JS -->
    </div>

    <div class="typing-indicator" id="typing-indicator"></div>

    <div class="input-bar">
      <!-- Reply bar -->
      <div class="reply-bar" id="reply-bar">
        <span>↩️ Replying to <strong id="reply-name"></strong>: <span id="reply-preview-text"></span></span>
        <span class="reply-close" onclick="cancelReply()">✕</span>
      </div>

      <!-- Emoji picker -->
      <div class="emoji-picker" id="emoji-picker">
        <?php
          $emojis = ['😊','😂','❤️','🔥','👍','👎','😍','🤔','😭','🙏','💯','🐝','✅','😎','🎉','💬'];
          foreach ($emojis as $e) echo "<span onclick=\"insertEmoji('$e')\">$e</span>";
        ?>
      </div>

      <!-- Input row -->
      <div class="input-row">
        <div class="input-actions">
          <label class="action-btn" for="media-input" title="Share image">📎</label>
          <input type="file" id="media-input" accept="image/*" style="display:none" onchange="sendImage(this)"/>
          <div class="action-btn" onclick="toggleEmoji()" title="Emoji">😊</div>
        </div>
        <textarea id="msg-input" placeholder="Type a message..." rows="1"
                  onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
        <button class="send-btn" onclick="sendMessage()">
          <svg viewBox="0 0 24 24"><path d="M2 21l21-9L2 3v7l15 2-15 2v7z"/></svg>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Context Menu -->
<div class="ctx-menu" id="ctx-menu">
  <div class="ctx-item" onclick="replyTo()">↩️ Reply</div>
  <div class="ctx-item" onclick="reactTo()">😊 React</div>
  <div class="ctx-item danger" onclick="deleteMsg()" id="ctx-delete" style="display:none">🗑️ Delete</div>
</div>

<script>
const ROOM_ID   = <?= $room_id ?>;
const MY_ID     = <?= $user['user_id'] ?>;
const MY_NAME   = "<?= addslashes($user['username']) ?>";
let lastMsgId   = 0;
let replyToId   = null;
let ctxMsgId    = null;
let ctxIsMine   = false;
let pollTimer   = null;

// ── INIT ──
window.addEventListener('load', () => {
  fetchMessages();
  pollTimer = setInterval(fetchMessages, 2000);
  updateStatus('online');
  window.addEventListener('beforeunload', () => updateStatus('offline'));
});

// ── FETCH MESSAGES ──
async function fetchMessages() {
  try {
    const res  = await fetch(`/hive/api/get_messages.php?room_id=${ROOM_ID}&since=${lastMsgId}`);
    const msgs = await res.json();
    if (msgs.length > 0) {
      msgs.forEach(appendMessage);
      lastMsgId = msgs[msgs.length - 1].message_id;
      scrollBottom();
    }
  } catch(e) { console.error(e); }
}

// ── APPEND MESSAGE ──
function appendMessage(msg) {
  const wrap    = document.getElementById('messages-wrap');
  const isMine  = msg.is_mine;
  const row     = document.createElement('div');
  row.className = 'msg-row' + (isMine ? ' mine' : '');
  row.dataset.id    = msg.message_id;
  row.dataset.mine  = isMine ? '1' : '0';
  row.dataset.user  = msg.username;

  // Avatar
  let avHtml = '';
  if (!isMine) {
    avHtml = `<img src="${msg.avatar_url}" class="msg-av"
               onerror="this.outerHTML='<div class=\\'msg-av-placeholder\\'>${msg.username[0].toUpperCase()}</div>'"
               alt="${msg.username}"/>`;
  }

  // Reply preview
  let replyHtml = '';
  if (msg.reply_content) {
    replyHtml = `<div class="reply-preview">
      <strong>${msg.reply_username}</strong><br/>
      ${escHtml(msg.reply_content.substring(0, 60))}
    </div>`;
  }

  // Bubble content
  let bubbleContent = '';
  if (msg.msg_type === 'image' && msg.media_url) {
    bubbleContent = `<img src="/hive/assets/uploads/media/${msg.media_url}" alt="image"/>`;
  } else {
    bubbleContent = escHtml(msg.content);
  }

  // Reactions
  let reactHtml = '';
  if (msg.reactions) {
    const emojis = msg.reactions.split(',');
    reactHtml = `<div class="msg-reactions">${emojis.map(e => `<span class="reaction-pill">${e}</span>`).join('')}</div>`;
  }

  // Seen
  const seenHtml = isMine
    ? `<span class="msg-seen">${msg.seen_count > 0 ? '✓✓' : '✓'}</span>`
    : '';

  row.innerHTML = `
    ${!isMine ? avHtml : ''}
    <div class="msg-content">
      ${!isMine ? `<div class="msg-username">${escHtml(msg.username)}</div>` : ''}
      ${replyHtml}
      <div class="msg-bubble" oncontextmenu="showCtx(event, ${msg.message_id}, ${isMine ? 1 : 0})"
           onclick="showCtx(event, ${msg.message_id}, ${isMine ? 1 : 0})">
        ${bubbleContent}
      </div>
      ${reactHtml}
      <div class="msg-meta">
        <span class="msg-time">${msg.time_label}</span>
        ${seenHtml}
      </div>
    </div>
    ${isMine ? avHtml : ''}
  `;

  wrap.appendChild(row);
}

// ── SEND MESSAGE ──
async function sendMessage() {
  const input   = document.getElementById('msg-input');
  const content = input.value.trim();
  if (!content) return;

  const form = new FormData();
  form.append('room_id',  ROOM_ID);
  form.append('content',  content);
  if (replyToId) form.append('reply_to', replyToId);

  input.value = '';
  autoResize(input);
  cancelReply();

  try {
    await fetch('/hive/api/send_message.php', { method: 'POST', body: form });
    fetchMessages();
  } catch(e) { console.error(e); }
}

// ── SEND IMAGE ──
async function sendImage(input) {
  if (!input.files[0]) return;
  const form = new FormData();
  form.append('room_id', ROOM_ID);
  form.append('content', '');
  form.append('media',   input.files[0]);
  try {
    await fetch('/hive/api/send_message.php', { method: 'POST', body: form });
    fetchMessages();
  } catch(e) { console.error(e); }
  input.value = '';
}

// ── CONTEXT MENU ──
function showCtx(e, msgId, isMine) {
  e.preventDefault();
  ctxMsgId  = msgId;
  ctxIsMine = isMine;
  const menu = document.getElementById('ctx-menu');
  document.getElementById('ctx-delete').style.display = isMine ? 'flex' : 'none';
  menu.style.left = Math.min(e.clientX, window.innerWidth  - 170) + 'px';
  menu.style.top  = Math.min(e.clientY, window.innerHeight - 120) + 'px';
  menu.classList.add('open');
}

document.addEventListener('click', e => {
  if (!e.target.closest('#ctx-menu') && !e.target.closest('.msg-bubble')) {
    document.getElementById('ctx-menu').classList.remove('open');
  }
});

// ── REPLY ──
function replyTo() {
  const row  = document.querySelector(`.msg-row[data-id="${ctxMsgId}"]`);
  if (!row) return;
  replyToId  = ctxMsgId;
  document.getElementById('reply-name').textContent         = row.dataset.user;
  document.getElementById('reply-preview-text').textContent =
    row.querySelector('.msg-bubble')?.textContent.trim().substring(0, 50) || '';
  document.getElementById('reply-bar').classList.add('active');
  document.getElementById('msg-input').focus();
  document.getElementById('ctx-menu').classList.remove('open');
}

function cancelReply() {
  replyToId = null;
  document.getElementById('reply-bar').classList.remove('active');
}

// ── REACT ──
async function reactTo() {
  const emoji = prompt('Enter an emoji to react:');
  if (!emoji) return;
  const form = new FormData();
  form.append('message_id', ctxMsgId);
  form.append('emoji',      emoji);
  await fetch('/hive/api/react.php', { method: 'POST', body: form });
  fetchMessages();
  document.getElementById('ctx-menu').classList.remove('open');
}

// ── DELETE ──
async function deleteMsg() {
  if (!ctxIsMine) return;
  if (!confirm('Delete this message?')) return;
  const form = new FormData();
  form.append('message_id', ctxMsgId);
  await fetch('/hive/api/delete_message.php', { method: 'POST', body: form });
  document.querySelector(`.msg-row[data-id="${ctxMsgId}"]`)?.remove();
  document.getElementById('ctx-menu').classList.remove('open');
}

// ── EMOJI ──
function toggleEmoji() {
  document.getElementById('emoji-picker').classList.toggle('open');
}

function insertEmoji(e) {
  const input = document.getElementById('msg-input');
  input.value += e;
  input.focus();
}

// ── HELPERS ──
function handleKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
}

function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

function scrollBottom() {
  const wrap = document.getElementById('messages-wrap');
  wrap.scrollTop = wrap.scrollHeight;
}

function escHtml(str) {
  if (!str) return '';
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

async function updateStatus(status) {
  const form = new FormData();
  form.append('status', status);
  await fetch('/hive/api/update_status.php', { method: 'POST', body: form });
}
</script>
</body>
</html>