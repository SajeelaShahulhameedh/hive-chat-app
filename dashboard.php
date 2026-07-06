<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();
$user = getCurrentUser();

// Update status to online
mysqli_query($conn, "UPDATE users SET user_status='online', last_seen=NOW() 
                     WHERE user_id={$user['user_id']}");

// Fetch all rooms with member count and last message
$rooms_query = mysqli_query($conn, "
    SELECT r.*,
           COUNT(DISTINCT rm.user_id) as member_count,
           (SELECT content FROM messages 
            WHERE room_id = r.room_id 
            AND is_deleted = 0 
            ORDER BY sent_at DESC LIMIT 1) as last_message,
           (SELECT sent_at FROM messages 
            WHERE room_id = r.room_id 
            AND is_deleted = 0 
            ORDER BY sent_at DESC LIMIT 1) as last_message_time,
           (SELECT COUNT(*) FROM room_members 
            WHERE room_id = r.room_id 
            AND user_id = {$user['user_id']}) as is_member
    FROM rooms r
    LEFT JOIN room_members rm ON r.room_id = rm.room_id
    GROUP BY r.room_id
    ORDER BY last_message_time DESC
");

$rooms = [];
while ($row = mysqli_fetch_assoc($rooms_query)) {
    $rooms[] = $row;
}

// Fetch online users count
$online = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE user_status='online'");
$online_count = mysqli_fetch_assoc($online)['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HIVE — Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --amber:  #F5A623;
      --amber2: #E09010;
      --dark:   #0E0B1A;
      --navy:   #1A1430;
      --card:   #201840;
      --purple: #7F77DD;
      --soft:   #C29BF5;
      --green:  #22C55E;
      --red:    #EF4444;
      --muted:  #9490B0;
      --text:   #FFFFFF;
      --border: rgba(245,166,35,0.1);
    }

    body {
      font-family: 'Space Grotesk', sans-serif;
      background: var(--dark);
      color: var(--text);
      min-height: 100vh;
    }

    .hex-bg {
      position: fixed;
      inset: 0;
      z-index: 0;
      opacity: 0.03;
      pointer-events: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='52'%3E%3Cpolygon points='30,2 58,17 58,46 30,51 2,46 2,17' fill='none' stroke='%23F5A623' stroke-width='1'/%3E%3C/svg%3E");
      background-size: 60px 52px;
    }

    /* NAVBAR */
    nav {
      position: sticky;
      top: 0;
      z-index: 100;
      background: rgba(14,11,26,0.92);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
      padding: 0 32px;
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .nav-logo {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }

    .nav-logo span {
      font-family: 'Outfit', sans-serif;
      font-size: 22px;
      font-weight: 900;
      letter-spacing: 6px;
      color: var(--text);
    }

    .nav-right {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .online-pill {
      display: flex;
      align-items: center;
      gap: 6px;
      background: rgba(34,197,94,0.1);
      border: 1px solid rgba(34,197,94,0.2);
      border-radius: 50px;
      padding: 6px 14px;
      font-size: 12px;
      color: var(--green);
      font-weight: 600;
    }

    .online-pill::before {
      content: '';
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--green);
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%,100% { opacity:1; transform:scale(1); }
      50%      { opacity:0.5; transform:scale(1.4); }
    }

    .nav-avatar {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--amber);
      cursor: pointer;
      text-decoration: none;
    }

    .nav-avatar-placeholder {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: var(--amber);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 14px;
      color: var(--dark);
      border: 2px solid var(--amber);
      cursor: pointer;
      text-decoration: none;
    }

    .logout-btn {
      background: rgba(239,68,68,0.1);
      border: 1px solid rgba(239,68,68,0.2);
      color: #FCA5A5;
      border-radius: 50px;
      padding: 7px 16px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.2s;
      font-family: 'Space Grotesk', sans-serif;
    }

    .logout-btn:hover { background: rgba(239,68,68,0.2); }

    /* MAIN LAYOUT */
    .container {
      position: relative;
      z-index: 1;
      max-width: 900px;
      margin: 0 auto;
      padding: 40px 24px;
    }

    /* HEADER */
    .page-header {
      margin-bottom: 36px;
    }

    .page-header h1 {
      font-family: 'Outfit', sans-serif;
      font-size: 32px;
      font-weight: 800;
      color: var(--text);
      margin-bottom: 6px;
    }

    .page-header h1 span { color: var(--amber); }

    .page-header p {
      font-size: 14px;
      color: var(--muted);
    }

    /* CREATE ROOM */
    .create-room-card {
      background: var(--navy);
      border: 1px dashed rgba(245,166,35,0.3);
      border-radius: 20px;
      padding: 28px;
      margin-bottom: 32px;
      transition: border-color 0.2s;
    }

    .create-room-card:hover { border-color: rgba(245,166,35,0.6); }

    .create-room-card h3 {
      font-family: 'Outfit', sans-serif;
      font-size: 17px;
      font-weight: 700;
      margin-bottom: 16px;
      color: var(--text);
    }

    .create-form {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .create-form input {
      flex: 1;
      min-width: 180px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 12px;
      padding: 12px 16px;
      font-family: 'Space Grotesk', sans-serif;
      font-size: 14px;
      color: var(--text);
      outline: none;
      transition: border-color 0.2s;
    }

    .create-form input:focus { border-color: var(--amber); }
    .create-form input::placeholder { color: var(--muted); }

    .btn-create {
      background: var(--amber);
      color: var(--dark);
      border: none;
      border-radius: 12px;
      padding: 12px 24px;
      font-family: 'Outfit', sans-serif;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s;
      white-space: nowrap;
    }

    .btn-create:hover { background: var(--amber2); }

    /* SECTION LABEL */
    .section-label {
      font-size: 11px;
      letter-spacing: 3px;
      color: var(--amber);
      font-weight: 600;
      text-transform: uppercase;
      margin-bottom: 16px;
    }

    /* ROOMS GRID */
    .rooms-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 16px;
    }

    .room-card {
      background: var(--navy);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 24px;
      cursor: pointer;
      text-decoration: none;
      color: var(--text);
      display: block;
      transition: border-color 0.2s, transform 0.2s;
      position: relative;
      overflow: hidden;
    }

    .room-card:hover {
      border-color: rgba(245,166,35,0.4);
      transform: translateY(-3px);
    }

    .room-card-top {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 16px;
    }

    .room-avatar {
      width: 48px;
      height: 48px;
      border-radius: 14px;
      object-fit: cover;
      background: rgba(245,166,35,0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      flex-shrink: 0;
    }

    .room-avatar img {
      width: 100%;
      height: 100%;
      border-radius: 14px;
      object-fit: cover;
    }

    .room-info h4 {
      font-family: 'Outfit', sans-serif;
      font-size: 16px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 3px;
    }

    .room-info p {
      font-size: 12px;
      color: var(--muted);
    }

    .room-last-msg {
      font-size: 13px;
      color: var(--muted);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 14px;
      line-height: 1.5;
    }

    .room-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .member-badge {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 12px;
      color: var(--muted);
    }

    .join-badge {
      font-size: 11px;
      font-weight: 700;
      padding: 4px 12px;
      border-radius: 50px;
      letter-spacing: 0.5px;
    }

    .join-badge.member {
      background: rgba(34,197,94,0.1);
      color: var(--green);
      border: 1px solid rgba(34,197,94,0.2);
    }

    .join-badge.join {
      background: rgba(245,166,35,0.1);
      color: var(--amber);
      border: 1px solid rgba(245,166,35,0.2);
    }

    .time-badge {
      font-size: 11px;
      color: var(--muted);
    }

    /* EMPTY STATE */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--muted);
    }

    .empty-state .icon { font-size: 48px; margin-bottom: 16px; }
    .empty-state h3 {
      font-family: 'Outfit', sans-serif;
      font-size: 20px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 8px;
    }

    /* ALERT */
    .alert {
      border-radius: 12px;
      padding: 12px 16px;
      font-size: 14px;
      margin-bottom: 20px;
    }
    .alert-error {
      background: rgba(239,68,68,0.1);
      border: 1px solid rgba(239,68,68,0.3);
      color: #FCA5A5;
    }
    .alert-success {
      background: rgba(34,197,94,0.1);
      border: 1px solid rgba(34,197,94,0.2);
      color: #86EFAC;
    }
  </style>
</head>
<body>
<div class="hex-bg"></div>

<!-- NAVBAR -->
<nav>
  <a href="/hive/dashboard.php" class="nav-logo">
    <svg width="28" height="32" viewBox="0 0 160 180">
      <polygon points="80,0 155,43 155,137 80,180 5,137 5,43" fill="#1A1430" stroke="#F5A623" stroke-width="8"/>
      <rect x="38" y="56" width="84" height="52" rx="13" fill="#F5A623"/>
      <polygon points="52,108 38,132 70,108" fill="#F5A623"/>
      <circle cx="59" cy="82" r="7" fill="#1A1430"/>
      <circle cx="80" cy="82" r="7" fill="#1A1430"/>
      <circle cx="101" cy="82" r="7" fill="#1A1430"/>
    </svg>
    <span>HIVE</span>
  </a>
  <div class="nav-right">
    <div class="online-pill"><?= $online_count ?> online</div>
    <a href="/hive/profile.php">
      <?php
        $pic_path = '/hive/assets/uploads/avatars/' . $user['profile_pic'];
        $full_path = $_SERVER['DOCUMENT_ROOT'] . $pic_path;
        if ($user['profile_pic'] !== 'default_avatar.png' && file_exists($full_path)):
      ?>
        <img src="<?= $pic_path ?>" class="nav-avatar" alt="avatar"/>
      <?php else: ?>
        <div class="nav-avatar-placeholder">
          <?= strtoupper(substr($user['username'], 0, 1)) ?>
        </div>
      <?php endif; ?>
    </a>
    <a href="/hive/logout.php" class="logout-btn">Logout</a>
  </div>
</nav>

<!-- MAIN -->
<div class="container">

  <!-- Header -->
  <div class="page-header">
    <h1>Welcome back, <span><?= htmlspecialchars($user['username']) ?></span> 🐝</h1>
    <p>Join a room or create your own to start chatting</p>
  </div>

  <?php
  $msg = '';
  $msg_type = '';
  if (isset($_GET['created']))  { $msg = "Room created successfully!"; $msg_type = 'success'; }
  if (isset($_GET['joined']))   { $msg = "You joined the room!";        $msg_type = 'success'; }
  if (isset($_GET['error']))    { $msg = htmlspecialchars($_GET['error']); $msg_type = 'error'; }
  if ($msg):
  ?>
    <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
  <?php endif; ?>

  <!-- Create Room -->
  <div class="create-room-card">
    <h3>🏠 Create a new room</h3>
    <form class="create-form" method="POST" action="/hive/api/get_rooms.php">
      <input type="text" name="room_name" placeholder="Room name e.g. Study Group" maxlength="100" required/>
      <input type="text" name="description" placeholder="Short description (optional)" maxlength="255"/>
      <button type="submit" class="btn-create">+ Create</button>
    </form>
  </div>

  <!-- Rooms List -->
  <p class="section-label">All Rooms</p>

  <?php if (empty($rooms)): ?>
    <div class="empty-state">
      <div class="icon">🐝</div>
      <h3>No rooms yet</h3>
      <p>Be the first to create a room above!</p>
    </div>
  <?php else: ?>
    <div class="rooms-grid">
      <?php foreach ($rooms as $room): ?>
        <a href="/hive/chat.php?room=<?= $room['room_id'] ?>" class="room-card">
          <div class="room-card-top">
            <div class="room-avatar">
              <?php
                $rpic = '/hive/assets/uploads/avatars/' . $room['room_pic'];
                if ($room['room_pic'] !== 'default_room.png' && file_exists($_SERVER['DOCUMENT_ROOT'] . $rpic)):
              ?>
                <img src="<?= $rpic ?>" alt="room"/>
              <?php else: ?>
                🐝
              <?php endif; ?>
            </div>
            <div class="room-info">
              <h4><?= htmlspecialchars($room['room_name']) ?></h4>
              <p><?= htmlspecialchars($room['description'] ?: 'No description') ?></p>
            </div>
          </div>
          <div class="room-last-msg">
            <?= $room['last_message']
                ? '💬 ' . htmlspecialchars(substr($room['last_message'], 0, 60)) . (strlen($room['last_message']) > 60 ? '...' : '')
                : 'No messages yet — be the first!' ?>
          </div>
          <div class="room-footer">
            <span class="member-badge">
              👥 <?= $room['member_count'] ?> member<?= $room['member_count'] != 1 ? 's' : '' ?>
            </span>
            <span class="join-badge <?= $room['is_member'] ? 'member' : 'join' ?>">
              <?= $room['is_member'] ? '✓ Joined' : '+ Join' ?>
            </span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

</body>
</html>