<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header("Location: /hive/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "All fields are required.";
    } else {
        $sql    = "SELECT * FROM users WHERE username='$username' OR email='$username' LIMIT 1";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            if (password_verify($password, $user['password_hash'])) {

                $_SESSION['user_id']     = $user['user_id'];
                $_SESSION['username']    = $user['username'];
                $_SESSION['profile_pic'] = $user['profile_pic'];

                mysqli_query($conn, "UPDATE users SET user_status='online',
                             last_seen=NOW() WHERE user_id={$user['user_id']}");

                header("Location: /hive/dashboard.php");
                exit();
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "No account found with that username or email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HIVE — Login</title>
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
    }

    body {
      font-family: 'Space Grotesk', sans-serif;
      background: var(--dark);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
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
      max-width: 440px;
    }

    .logo-wrap {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      margin-bottom: 40px;
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

    .form-group { margin-bottom: 20px; }

    label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: var(--muted);
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-bottom: 8px;
    }

    .input-wrap { position: relative; }

    input[type="text"],
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

    input:focus { border-color: var(--amber); }
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

    .remember-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 28px;
      margin-top: -4px;
    }

    .remember-row label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: var(--muted);
      text-transform: none;
      letter-spacing: 0;
      cursor: pointer;
      margin: 0;
    }

    .remember-row input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--amber);
      cursor: pointer;
    }

    .forgot-link {
      font-size: 13px;
      color: var(--amber);
      text-decoration: none;
    }

    .forgot-link:hover { text-decoration: underline; }

    .alert {
      border-radius: 12px;
      padding: 12px 16px;
      font-size: 14px;
      margin-bottom: 24px;
      text-align: center;
    }

    .alert-error {
      background: rgba(239,68,68,0.1);
      border: 1px solid rgba(239,68,68,0.3);
      color: #FCA5A5;
    }

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
    }

    .btn-submit:hover {
      background: var(--amber2);
      transform: translateY(-1px);
    }

    .divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 28px 0;
    }

    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: rgba(255,255,255,0.08);
    }

    .divider span {
      font-size: 12px;
      color: var(--muted);
      letter-spacing: 1px;
    }

    .register-link {
      text-align: center;
      font-size: 14px;
      color: var(--muted);
    }

    .register-link a {
      color: var(--amber);
      text-decoration: none;
      font-weight: 600;
    }

    .register-link a:hover { text-decoration: underline; }

    /* Animated welcome text */
    .welcome-badge {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background: rgba(245,166,35,0.08);
      border: 1px solid rgba(245,166,35,0.2);
      border-radius: 50px;
      padding: 8px 20px;
      margin-bottom: 32px;
      font-size: 13px;
      color: var(--amber);
      font-weight: 500;
    }

    .welcome-badge::before {
      content: '';
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--green);
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%,100% { opacity:1; transform:scale(1); }
      50%      { opacity:0.5; transform:scale(1.3); }
    }
  </style>
</head>
<body>
<div class="hex-bg"></div>

<div class="card">

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
    <span class="logo-sub">Welcome back</span>
  </div>

  <div class="welcome-badge">
    Hive is active — login to join
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">

    <div class="form-group">
      <label>Username or Email</label>
      <input type="text" name="username"
             placeholder="Enter your username or email"
             value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
             required/>
    </div>

    <div class="form-group">
      <label>Password</label>
      <div class="input-wrap">
        <input type="password" name="password" id="pw"
               placeholder="Enter your password" required/>
        <span class="toggle-pw" onclick="togglePw()">👁</span>
      </div>
    </div>

    <div class="remember-row">
      <label>
        <input type="checkbox" name="remember"/>
        Remember me
      </label>
      <a href="#" class="forgot-link">Forgot password?</a>
    </div>

    <button type="submit" class="btn-submit">Login to HIVE 🐝</button>
  </form>

  <div class="divider"><span>OR</span></div>

  <div class="register-link">
    New to HIVE? <a href="/hive/register.php">Create an account</a>
  </div>

</div>

<script>
function togglePw() {
  const input = document.getElementById('pw');
  const btn   = document.querySelector('.toggle-pw');
  if (input.type === 'password') {
    input.type   = 'text';
    btn.textContent = '🙈';
  } else {
    input.type   = 'password';
    btn.textContent = '👁';
  }
}
</script>
</body>
</html>