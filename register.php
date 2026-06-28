<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header("Location: /hive/dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email    = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $check = mysqli_query($conn, "SELECT user_id FROM users WHERE username='$username' OR email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Username or email already exists.";
        } else {
            $profile_pic = 'default_avatar.png';

            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $mime    = $_FILES['profile_pic']['type'];
                if (in_array($mime, $allowed)) {
                    $ext      = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                    $filename = 'avatar_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    $dest     = __DIR__ . '/assets/uploads/avatars/' . $filename;
                    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest)) {
                        $profile_pic = $filename;
                    }
                }
            }

            $hash = md5($password);
            $sql  = "INSERT INTO users (username, email, password_hash, profile_pic)
                     VALUES ('$username', '$email', '$hash', '$profile_pic')";

            if (mysqli_query($conn, $sql)) {
                $user_id = mysqli_insert_id($conn);

                $auto_join = mysqli_query($conn, "SELECT room_id FROM rooms WHERE room_name='General' LIMIT 1");
                if ($auto_join && mysqli_num_rows($auto_join) > 0) {
                    $room = mysqli_fetch_assoc($auto_join);
                    mysqli_query($conn, "INSERT INTO room_members (room_id, user_id, role)
                                        VALUES ({$room['room_id']}, $user_id, 'member')");
                }

                $_SESSION['user_id']     = $user_id;
                $_SESSION['username']    = $username;
                $_SESSION['profile_pic'] = $profile_pic;

                header("Location: /hive/dashboard.php");
                exit();
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HIVE — Join the Hive</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --amber:  #F5A623;
      --amber2: #E09010;
      --dark:   #0E0B1A;
      --navy:   #1A1430;
      --purple: #7F77DD;
      --soft:   #C29BF5;
      --green:  #22C55E;
      --red:    #EF4444;
      --muted:  #9490B0;
      --text:   #FFFFFF;
    }

    body {
      font-family: 'Space Grotesk', sans-serif;
      background: var(--dark);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .hex-bg {
      position: fixed;
      inset: 0;
      z-index: 0;
      opacity: 0.04;
      pointer-events: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='52'%3E%3Cpolygon points='30,2 58,17 58,46 30,51 2,46 2,17' fill='none' stroke='%23F5A623' stroke-width='1'/%3E%3C/svg%3E");
      background-size: 60px 52px;
    }

    .card {
      position: relative;
      z-index: 1;
      background: var(--navy);
      border: 1px solid rgba(245,166,35,0.15);
      border-radius: 28px;
      padding: 48px 44px;
      width: 100%;
      max-width: 480px;
    }

    .logo-wrap {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      margin-bottom: 36px;
    }

    .logo-text {
      font-family: 'Outfit', sans-serif;
      font-size: 32px;
      font-weight: 900;
      letter-spacing: 8px;
      color: var(--text);
    }

    .logo-sub {
      font-size: 12px;
      color: var(--muted);
      letter-spacing: 2px;
      text-transform: uppercase;
    }

    /* Avatar upload */
    .avatar-upload {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 28px;
      gap: 10px;
    }

    .avatar-ring {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      border: 2.5px dashed rgba(245,166,35,0.4);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      overflow: hidden;
      transition: border-color 0.2s;
      position: relative;
    }

    .avatar-ring:hover { border-color: var(--amber); }

    .avatar-ring img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: none;
    }

    .avatar-ring .placeholder {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
    }

    .avatar-ring .placeholder span:first-child {
      font-size: 28px;
    }

    .avatar-ring .placeholder span:last-child {
      font-size: 10px;
      color: var(--muted);
      letter-spacing: 1px;
    }

    .avatar-label {
      font-size: 12px;
      color: var(--muted);
    }

    /* Form */
    .form-group {
      margin-bottom: 18px;
    }

    label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: var(--muted);
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-bottom: 8px;
    }

    .input-wrap {
      position: relative;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
      width: 100%;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 14px;
      padding: 14px 18px;
      font-family: 'Space Grotesk', sans-serif;
      font-size: 15px;
      color: var(--text);
      outline: none;
      transition: border-color 0.2s;
    }

    input:focus {
      border-color: var(--amber);
    }

    input::placeholder { color: var(--muted); }

    .toggle-pw {
      position: absolute;
      right: 16px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      font-size: 16px;
      color: var(--muted);
      user-select: none;
    }

    /* Password strength */
    .strength-bar {
      display: flex;
      gap: 4px;
      margin-top: 8px;
    }

    .strength-bar span {
      flex: 1;
      height: 3px;
      border-radius: 4px;
      background: rgba(255,255,255,0.1);
      transition: background 0.3s;
    }

    .strength-label {
      font-size: 11px;
      color: var(--muted);
      margin-top: 4px;
    }

    /* Error / success */
    .alert {
      border-radius: 12px;
      padding: 12px 16px;
      font-size: 14px;
      margin-bottom: 20px;
      text-align: center;
    }

    .alert-error {
      background: rgba(239,68,68,0.1);
      border: 1px solid rgba(239,68,68,0.3);
      color: #FCA5A5;
    }

    /* Submit */
    .btn-submit {
      width: 100%;
      background: var(--amber);
      color: var(--dark);
      border: none;
      border-radius: 50px;
      padding: 16px;
      font-family: 'Outfit', sans-serif;
      font-size: 17px;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s, transform 0.1s;
      margin-top: 8px;
    }

    .btn-submit:hover {
      background: var(--amber2);
      transform: translateY(-1px);
    }

    .login-link {
      text-align: center;
      margin-top: 24px;
      font-size: 14px;
      color: var(--muted);
    }

    .login-link a {
      color: var(--amber);
      text-decoration: none;
      font-weight: 600;
    }

    .login-link a:hover { text-decoration: underline; }
  </style>
</head>
<body>
<div class="hex-bg"></div>

<div class="card">

  <!-- Logo -->
  <div class="logo-wrap">
    <svg width="48" height="54" viewBox="0 0 160 180">
      <polygon points="80,0 155,43 155,137 80,180 5,137 5,43" fill="#0E0B1A" stroke="#F5A623" stroke-width="8"/>
      <rect x="38" y="56" width="84" height="52" rx="13" fill="#F5A623"/>
      <polygon points="52,108 38,132 70,108" fill="#F5A623"/>
      <circle cx="59" cy="82" r="7" fill="#0E0B1A"/>
      <circle cx="80" cy="82" r="7" fill="#0E0B1A"/>
      <circle cx="101" cy="82" r="7" fill="#0E0B1A"/>
    </svg>
    <span class="logo-text">HIVE</span>
    <span class="logo-sub">Create your account</span>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">

    <!-- Avatar upload -->
    <div class="avatar-upload">
      <div class="avatar-ring" onclick="document.getElementById('pic').click()">
        <img id="preview" src="" alt="preview"/>
        <div class="placeholder" id="placeholder">
          <span>🐝</span>
          <span>PHOTO</span>
        </div>
      </div>
      <span class="avatar-label">Tap to upload profile picture</span>
      <input type="file" id="pic" name="profile_pic" accept="image/*" style="display:none" onchange="previewAvatar(this)"/>
    </div>

    <!-- Username -->
    <div class="form-group">
      <label>Username</label>
      <input type="text" name="username" placeholder="e.g. sajeela" maxlength="50"
             value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required/>
    </div>

    <!-- Email -->
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" placeholder="you@example.com"
             value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required/>
    </div>

    <!-- Password -->
    <div class="form-group">
      <label>Password</label>
      <div class="input-wrap">
        <input type="password" name="password" id="pw" placeholder="Min. 6 characters"
               oninput="checkStrength(this.value)" required/>
        <span class="toggle-pw" onclick="togglePw('pw', this)">👁</span>
      </div>
      <div class="strength-bar">
        <span id="s1"></span>
        <span id="s2"></span>
        <span id="s3"></span>
        <span id="s4"></span>
      </div>
      <div class="strength-label" id="strength-text"></div>
    </div>

    <!-- Confirm Password -->
    <div class="form-group">
      <label>Confirm Password</label>
      <div class="input-wrap">
        <input type="password" name="confirm_password" id="cpw" placeholder="Repeat your password" required/>
        <span class="toggle-pw" onclick="togglePw('cpw', this)">👁</span>
      </div>
    </div>

    <button type="submit" class="btn-submit">Join the Hive 🐝</button>
  </form>

  <div class="login-link">
    Already have an account? <a href="/hive/login.php">Login</a>
  </div>

</div>

<script>
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.getElementById('preview');
      img.src = e.target.result;
      img.style.display = 'block';
      document.getElementById('placeholder').style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function togglePw(id, el) {
  const input = document.getElementById(id);
  if (input.type === 'password') {
    input.type = 'text';
    el.textContent = '🙈';
  } else {
    input.type = 'password';
    el.textContent = '👁';
  }
}

function checkStrength(val) {
  const bars   = [document.getElementById('s1'), document.getElementById('s2'),
                  document.getElementById('s3'), document.getElementById('s4')];
  const label  = document.getElementById('strength-text');
  const colors = ['#EF4444','#F5A623','#7F77DD','#22C55E'];
  const labels = ['Too short','Weak','Good','Strong'];

  let score = 0;
  if (val.length >= 6)                         score++;
  if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
  if (/[0-9]/.test(val))                       score++;
  if (/[^A-Za-z0-9]/.test(val))               score++;

  bars.forEach((b, i) => {
    b.style.background = i < score ? colors[score - 1] : 'rgba(255,255,255,0.1)';
  });

  label.textContent  = val.length === 0 ? '' : labels[score - 1] || 'Too short';
  label.style.color  = val.length === 0 ? '' : colors[score - 1] || '#EF4444';
}
</script>
</body>
</html>