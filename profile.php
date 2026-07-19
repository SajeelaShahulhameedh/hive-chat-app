<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();
$user    = getCurrentUser();
$success = '';
$error   = '';

// Fetch full user data
$result  = mysqli_query($conn, "SELECT * FROM users WHERE user_id={$user['user_id']}");
$profile = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Update profile ──
    if (isset($_POST['update_profile'])) {
        $username    = sanitize($_POST['username']);
        $status_text = sanitize($_POST['status_text']);
        $user_status = sanitize($_POST['user_status']);

        $allowed_status = ['online','away','busy','offline'];
        if (!in_array($user_status, $allowed_status)) $user_status = 'online';

        if (strlen($username) < 3) {
            $error = "Username must be at least 3 characters.";
        } else {
            // Check username taken by someone else
            $check = mysqli_query($conn, "SELECT user_id FROM users
                                          WHERE username='$username'
                                          AND user_id != {$user['user_id']}");
            if (mysqli_num_rows($check) > 0) {
                $error = "That username is already taken.";
            } else {
                // Handle profile pic upload
                $profile_pic = $profile['profile_pic'];
                if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
                    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
                    if (in_array($_FILES['profile_pic']['type'], $allowed)) {
                        $ext      = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                        $filename = 'avatar_' . time() . '_' . rand(100,999) . '.' . $ext;
                        $dest     = __DIR__ . '/assets/uploads/avatars/' . $filename;
                        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest)) {
                            $profile_pic = $filename;
                        }
                    } else {
                        $error = "Only JPG, PNG, GIF, WEBP images allowed.";
                    }
                }

                if (!$error) {
                    mysqli_query($conn, "UPDATE users SET
                        username    = '$username',
                        status_text = '$status_text',
                        user_status = '$user_status',
                        profile_pic = '$profile_pic'
                        WHERE user_id = {$user['user_id']}");

                    $_SESSION['username']    = $username;
                    $_SESSION['profile_pic'] = $profile_pic;

                    $profile['username']    = $username;
                    $profile['status_text'] = $status_text;
                    $profile['user_status'] = $user_status;
                    $profile['profile_pic'] = $profile_pic;
                    $user['profile_pic']    = $profile_pic;

                    $success = "Profile updated successfully!";
                }
            }
        }
    }

    // ── Change password ──
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new     = $_POST['new_password'];
        $confirm = $_POST['confirm_new'];

        if (!password_verify($current, $profile['password_hash'])) {
            $error = "Current password is incorrect.";
        } elseif (strlen($new) < 6) {
            $error = "New password must be at least 6 characters.";
        } elseif ($new !== $confirm) {
            $error = "New passwords do not match.";
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password_hash='$hash'
                                 WHERE user_id={$user['user_id']}");
            $success = "Password changed successfully!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HIVE — My Profile</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --amber:  #F5A623;
      --amber2: #E09010;
      --dark:   #0E0B1A;
      --navy:   #1A1430;
      --purple: #7F77DD;
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
      inset: 0; z-index: 0;
      opacity: 0.03;
      pointer-events: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='52'%3E%3Cpolygon points='30,2 58,17 58,46 30,51 2,46 2,17' fill='none' stroke='%23F5A623' stroke-width='1'/%3E%3C/svg%3E");
      background-size: 60px 52px;
    }

    /* NAV */
    nav {
      position: sticky; top: 0; z-index: 100;
      background: rgba(14,11,26,0.92);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 32px;
    }

    .nav-logo {
      display: flex; align-items: center; gap: 10px;
      text-decoration: none;
    }

    .nav-logo span {
      font-family: 'Outfit', sans-serif;
      font-size: 22px; font-weight: 900;
      letter-spacing: 6px; color: var(--text);
    }

    .nav-right { display: flex; align-items: center; gap: 12px; }

    .nav-link {
      color: var(--muted); text-decoration: none;
      font-size: 14px; font-weight: 500;
      transition: color 0.2s;
    }

    .nav-link:hover { color: var(--amber); }

    .logout-btn {
      background: rgba(239,68,68,0.1);
      border: 1px solid rgba(239,68,68,0.2);
      color: #FCA5A5; border-radius: 50px;
      padding: 7px 16px; font-size: 13px;
      font-weight: 600; cursor: pointer;
      text-decoration: none;
      transition: background 0.2s;
    }

    .logout-btn:hover { background: rgba(239,68,68,0.2); }

    /* CONTAINER */
    .container {
      position: relative; z-index: 1;
      max-width: 760px;
      margin: 0 auto;
      padding: 40px 24px 80px;
    }

    .page-title {
      font-family: 'Outfit', sans-serif;
      font-size: 28px; font-weight: 800;
      margin-bottom: 32px;
      color: var(--text);
    }

    .page-title span { color: var(--amber); }

    /* ALERTS */
    .alert {
      border-radius: 12px; padding: 13px 16px;
      font-size: 14px; margin-bottom: 24px;
      text-align: center;
    }

    .alert-success {
      background: rgba(34,197,94,0.1);
      border: 1px solid rgba(34,197,94,0.25);
      color: #86EFAC;
    }

    .alert-error {
      background: rgba(239,68,68,0.1);
      border: 1px solid rgba(239,68,68,0.3);
      color: #FCA5A5;
    }

    /* CARDS */
    .card {
      background: var(--navy);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 32px;
      margin-bottom: 24px;
    }

    .card-title {
      font-family: 'Outfit', sans-serif;
      font-size: 17px; font-weight: 700;
      margin-bottom: 24px; color: var(--text);
      display: flex; align-items: center; gap: 8px;
    }

    /* AVATAR SECTION */
    .avatar-section {
      display: flex;
      align-items: center;
      gap: 24px;
      margin-bottom: 28px;
    }

    .avatar-wrap {
      position: relative;
      cursor: pointer;
      flex-shrink: 0;
    }

    .avatar-img {
      width: 90px; height: 90px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--amber);
    }

    .avatar-placeholder {
      width: 90px; height: 90px;
      border-radius: 50%;
      background: var(--purple);
      display: flex; align-items: center;
      justify-content: center;
      font-size: 32px; font-weight: 700;
      color: #fff;
      border: 3px solid var(--amber);
    }

    .avatar-edit-overlay {
      position: absolute; inset: 0;
      border-radius: 50%;
      background: rgba(0,0,0,0.5);
      display: flex; align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.2s;
      font-size: 20px;
    }

    .avatar-wrap:hover .avatar-edit-overlay { opacity: 1; }

    .avatar-info h3 {
      font-family: 'Outfit', sans-serif;
      font-size: 20px; font-weight: 800;
      margin-bottom: 4px;
    }

    .avatar-info p {
      font-size: 13px; color: var(--muted);
    }

    .avatar-info .member-since {
      font-size: 12px; color: var(--muted);
      margin-top: 4px;
    }

    /* FORM */
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .form-group { margin-bottom: 16px; }
    .form-group.full { grid-column: 1 / -1; }

    label {
      display: block;
      font-size: 11px; font-weight: 600;
      color: var(--muted);
      letter-spacing: 1.5px;
      text-transform: uppercase;
      margin-bottom: 8px;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    select,
    textarea {
      width: 100%;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 12px;
      padding: 13px 16px;
      font-family: 'Space Grotesk', sans-serif;
      font-size: 14px; color: var(--text);
      outline: none;
      transition: border-color 0.2s;
    }

    input:focus, select:focus, textarea:focus {
      border-color: var(--amber);
    }

    input::placeholder, textarea::placeholder { color: var(--muted); }

    select option { background: var(--navy); }

    textarea { resize: none; height: 80px; }

    .input-wrap { position: relative; }

    .toggle-pw {
      position: absolute; right: 14px;
      top: 50%; transform: translateY(-50%);
      cursor: pointer; font-size: 15px;
      color: var(--muted); user-select: none;
    }

    /* STATUS SELECT */
    .status-row {
      display: flex; gap: 10px; flex-wrap: wrap;
    }

    .status-opt {
      flex: 1; min-width: 100px;
      padding: 10px;
      border-radius: 12px;
      border: 1.5px solid rgba(255,255,255,0.08);
      text-align: center;
      cursor: pointer;
      font-size: 13px;
      transition: border-color 0.2s, background 0.2s;
    }

    .status-opt.active-online  { border-color: var(--green);  background: rgba(34,197,94,0.1); }
    .status-opt.active-away    { border-color: var(--amber);  background: rgba(245,166,35,0.1); }
    .status-opt.active-busy    { border-color: #EF4444; background: rgba(239,68,68,0.1); }
    .status-opt.active-offline { border-color: var(--muted);  background: rgba(148,144,176,0.1); }

    /* SUBMIT */
    .btn-save {
      background: var(--amber); color: var(--dark);
      border: none; border-radius: 50px;
      padding: 14px 36px;
      font-family: 'Outfit', sans-serif;
      font-size: 16px; font-weight: 700;
      cursor: pointer;
      transition: background 0.2s, transform 0.1s;
      margin-top: 8px;
    }

    .btn-save:hover {
      background: var(--amber2);
      transform: translateY(-1px);
    }

    .btn-danger {
      background: rgba(239,68,68,0.1);
      color: #FCA5A5;
      border: 1px solid rgba(239,68,68,0.3);
      border-radius: 50px;
      padding: 14px 36px;
      font-family: 'Outfit', sans-serif;
      font-size: 16px; font-weight: 700;
      cursor: pointer;
      transition: background 0.2s;
      margin-top: 8px;
    }

    .btn-danger:hover { background: rgba(239,68,68,0.2); }

    /* STATS ROW */
    .stats-row {
      display: flex; gap: 16px;
      margin-bottom: 28px;
      flex-wrap: wrap;
    }

    .stat-chip {
      background: rgba(245,166,35,0.07);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 14px 20px;
      flex: 1; min-width: 120px;
      text-align: center;
    }

    .stat-chip .num {
      font-family: 'Outfit', sans-serif;
      font-size: 24px; font-weight: 800;
      color: var(--amber);
    }

    .stat-chip .lbl {
      font-size: 12px; color: var(--muted);
      margin-top: 2px;
    }
  </style>
</head>
<body>
<div class="hex-bg"></div>

<!-- NAV -->
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
    <a href="/hive/dashboard.php" class="nav-link">← Dashboard</a>
    <a href="/hive/logout.php" class="logout-btn">Logout</a>
  </div>
</nav>

<div class="container">

  <h1 class="page-title">My <span>Profile</span> 👤</h1>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php
    // Stats
    $rooms_count = mysqli_fetch_assoc(mysqli_query($conn,
      "SELECT COUNT(*) as c FROM room_members WHERE user_id={$user['user_id']}"))['c'];
    $msg_count   = mysqli_fetch_assoc(mysqli_query($conn,
      "SELECT COUNT(*) as c FROM messages WHERE sender_id={$user['user_id']} AND is_deleted=0"))['c'];
  ?>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-chip">
      <div class="num"><?= $rooms_count ?></div>
      <div class="lbl">Rooms Joined</div>
    </div>
    <div class="stat-chip">
      <div class="num"><?= $msg_count ?></div>
      <div class="lbl">Messages Sent</div>
    </div>
    <div class="stat-chip">
      <div class="num"><?= date('M Y', strtotime($profile['created_at'])) ?></div>
      <div class="lbl">Member Since</div>
    </div>
  </div>

  <!-- Profile Card -->
  <div class="card">
    <div class="card-title">🐝 Edit Profile</div>

    <form method="POST" enctype="multipart/form-data">

      <!-- Avatar -->
      <div class="avatar-section">
        <div class="avatar-wrap" onclick="document.getElementById('pic').click()">
          <?php
            $picpath = '/hive/assets/uploads/avatars/' . $profile['profile_pic'];
            $fullpath = $_SERVER['DOCUMENT_ROOT'] . $picpath;
            if ($profile['profile_pic'] !== 'default_avatar.png' && file_exists($fullpath)):
          ?>
            <img src="<?= $picpath ?>" class="avatar-img" id="avatar-preview" alt="avatar"/>
          <?php else: ?>
            <div class="avatar-placeholder" id="avatar-preview">
              <?= strtoupper(substr($profile['username'],0,1)) ?>
            </div>
          <?php endif; ?>
          <div class="avatar-edit-overlay">✏️</div>
        </div>
        <input type="file" id="pic" name="profile_pic" accept="image/*"
               style="display:none" onchange="previewAvatar(this)"/>
        <div class="avatar-info">
          <h3><?= htmlspecialchars($profile['username']) ?></h3>
          <p><?= htmlspecialchars($profile['email']) ?></p>
          <p class="member-since">Member since <?= date('d M Y', strtotime($profile['created_at'])) ?></p>
        </div>
      </div>

      <!-- Fields -->
      <div class="form-grid">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" maxlength="50"
                 value="<?= htmlspecialchars($profile['username']) ?>" required/>
        </div>
        <div class="form-group">
          <label>Email (read only)</label>
          <input type="email" value="<?= htmlspecialchars($profile['email']) ?>" disabled/>
        </div>
        <div class="form-group full">
          <label>Status Text</label>
          <input type="text" name="status_text" maxlength="150"
                 value="<?= htmlspecialchars($profile['status_text']) ?>"
                 placeholder="What's on your mind?"/>
        </div>
      </div>

      <!-- Status picker -->
      <div class="form-group">
        <label>Availability</label>
        <input type="hidden" name="user_status" id="status-input"
               value="<?= $profile['user_status'] ?>"/>
        <div class="status-row">
          <?php
            $statuses = [
              'online'  => ['🟢', 'Online'],
              'away'    => ['🟡', 'Away'],
              'busy'    => ['🔴', 'Busy'],
              'offline' => ['⚫', 'Offline'],
            ];
            foreach ($statuses as $val => $info):
              $active = $profile['user_status'] === $val ? "active-$val" : '';
          ?>
            <div class="status-opt <?= $active ?>" onclick="setStatus('<?= $val ?>')">
              <?= $info[0] ?> <?= $info[1] ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <button type="submit" name="update_profile" class="btn-save">
        Save Changes ✓
      </button>
    </form>
  </div>

  <!-- Change Password Card -->
  <div class="card">
    <div class="card-title">🔐 Change Password</div>
    <form method="POST">
      <div class="form-group">
        <label>Current Password</label>
        <div class="input-wrap">
          <input type="password" name="current_password" id="cpw"
                 placeholder="Enter current password" required/>
          <span class="toggle-pw" onclick="togglePw('cpw',this)">👁</span>
        </div>
      </div>
      <div class="form-group">
        <label>New Password</label>
        <div class="input-wrap">
          <input type="password" name="new_password" id="npw"
                 placeholder="Min. 6 characters" required/>
          <span class="toggle-pw" onclick="togglePw('npw',this)">👁</span>
        </div>
      </div>
      <div class="form-group">
        <label>Confirm New Password</label>
        <div class="input-wrap">
          <input type="password" name="confirm_new" id="cnpw"
                 placeholder="Repeat new password" required/>
          <span class="toggle-pw" onclick="togglePw('cnpw',this)">👁</span>
        </div>
      </div>
      <button type="submit" name="change_password" class="btn-danger">
        Change Password 🔐
      </button>
    </form>
  </div>

</div>

<script>
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const prev = document.getElementById('avatar-preview');
      prev.outerHTML = `<img src="${e.target.result}"
        class="avatar-img" id="avatar-preview" alt="preview"/>`;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function setStatus(val) {
  document.getElementById('status-input').value = val;
  document.querySelectorAll('.status-opt').forEach(el => {
    el.className = 'status-opt';
  });
  event.target.closest('.status-opt').className = `status-opt active-${val}`;
}

function togglePw(id, el) {
  const input = document.getElementById(id);
  input.type  = input.type === 'password' ? 'text' : 'password';
  el.textContent = input.type === 'password' ? '👁' : '🙈';
}
</script>
</body>
</html>