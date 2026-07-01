<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: /hive/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HIVE — Your Community. Your Conversation.</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --amber:   #F5A623;
      --amber2:  #E09010;
      --dark:    #0E0B1A;
      --navy:    #1A1430;
      --purple:  #7F77DD;
      --soft-purple: #C29BF5;
      --green:   #22C55E;
      --text:    #FFFFFF;
      --muted:   #9490B0;
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'Space Grotesk', sans-serif;
      background: var(--dark);
      color: var(--text);
      overflow-x: hidden;
    }

    /* ── HONEYCOMB BACKGROUND ── */
    .hex-bg {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      z-index: 0;
      opacity: 0.04;
      pointer-events: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='52' %3E%3Cpolygon points='30,2 58,17 58,46 30,51 2,46 2,17' fill='none' stroke='%23F5A623' stroke-width='1'/%3E%3C/svg%3E");
      background-size: 60px 52px;
    }

    /* ── NAVBAR ── */
    nav {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 18px 60px;
      background: rgba(14,11,26,0.85);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(245,166,35,0.1);
    }

    .nav-logo {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }

    .nav-logo span {
      font-family: 'Outfit', sans-serif;
      font-size: 24px;
      font-weight: 900;
      letter-spacing: 6px;
      color: var(--text);
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 32px;
      list-style: none;
    }

    .nav-links a {
      color: var(--muted);
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      transition: color 0.2s;
    }

    .nav-links a:hover { color: var(--amber); }

    .nav-cta {
      background: var(--amber);
      color: var(--dark) !important;
      padding: 10px 24px;
      border-radius: 50px;
      font-weight: 700 !important;
      font-size: 14px !important;
      transition: background 0.2s !important;
    }

    .nav-cta:hover { background: var(--amber2) !important; }

    /* ── HERO ── */
    .hero {
      position: relative;
      z-index: 1;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 120px 20px 80px;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(245,166,35,0.1);
      border: 1px solid rgba(245,166,35,0.3);
      border-radius: 50px;
      padding: 8px 20px;
      font-size: 13px;
      color: var(--amber);
      font-weight: 600;
      letter-spacing: 1px;
      margin-bottom: 32px;
    }

    .hero-badge::before {
      content: '';
      width: 8px; height: 8px;
      border-radius: 50%;
      background: var(--green);
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%,100% { opacity: 1; transform: scale(1); }
      50%      { opacity: 0.5; transform: scale(1.3); }
    }

    .hero h1 {
      font-family: 'Outfit', sans-serif;
      font-size: clamp(48px, 8vw, 96px);
      font-weight: 900;
      letter-spacing: 12px;
      color: var(--text);
      line-height: 1;
      margin-bottom: 12px;
    }

    .hero h1 span { color: var(--amber); }

    .hero-tagline {
      font-family: 'Outfit', sans-serif;
      font-size: clamp(13px, 2vw, 16px);
      font-weight: 300;
      letter-spacing: 5px;
      color: var(--muted);
      text-transform: uppercase;
      margin-bottom: 28px;
    }

    .hero-desc {
      max-width: 520px;
      font-size: 17px;
      color: var(--muted);
      line-height: 1.8;
      margin-bottom: 48px;
    }

    .hero-buttons {
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
      justify-content: center;
    }

    .btn-primary {
      background: var(--amber);
      color: var(--dark);
      padding: 16px 40px;
      border-radius: 50px;
      font-family: 'Outfit', sans-serif;
      font-size: 16px;
      font-weight: 700;
      text-decoration: none;
      transition: background 0.2s, transform 0.1s;
      display: inline-block;
    }

    .btn-primary:hover {
      background: var(--amber2);
      transform: translateY(-2px);
    }

    .btn-ghost {
      background: transparent;
      color: var(--text);
      padding: 16px 40px;
      border-radius: 50px;
      border: 1.5px solid rgba(255,255,255,0.2);
      font-family: 'Outfit', sans-serif;
      font-size: 16px;
      font-weight: 600;
      text-decoration: none;
      transition: border-color 0.2s, transform 0.1s;
      display: inline-block;
    }

    .btn-ghost:hover {
      border-color: var(--amber);
      color: var(--amber);
      transform: translateY(-2px);
    }

    /* ── CHAT PREVIEW CARD ── */
    .preview-wrap {
      position: relative;
      z-index: 1;
      display: flex;
      justify-content: center;
      padding: 0 20px 100px;
    }

    .chat-preview {
      background: var(--navy);
      border: 1px solid rgba(245,166,35,0.2);
      border-radius: 24px;
      width: 100%;
      max-width: 680px;
      overflow: hidden;
    }

    .preview-header {
      background: rgba(245,166,35,0.08);
      border-bottom: 1px solid rgba(245,166,35,0.15);
      padding: 16px 24px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .preview-header .room-icon {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: var(--amber);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
    }

    .preview-header .room-info h4 {
      font-family: 'Outfit', sans-serif;
      font-size: 15px;
      font-weight: 700;
      color: var(--text);
    }

    .preview-header .room-info p {
      font-size: 12px;
      color: var(--green);
    }

    .preview-messages {
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .msg-row {
      display: flex;
      gap: 10px;
      align-items: flex-end;
    }

    .msg-row.mine {
      flex-direction: row-reverse;
    }

    .msg-avatar {
      width: 32px; height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 700;
      flex-shrink: 0;
    }

    .msg-bubble {
      max-width: 65%;
      padding: 12px 16px;
      border-radius: 18px;
      font-size: 14px;
      line-height: 1.5;
    }

    .msg-row:not(.mine) .msg-bubble {
      background: rgba(255,255,255,0.07);
      color: var(--text);
      border-bottom-left-radius: 4px;
    }

    .msg-row.mine .msg-bubble {
      background: var(--amber);
      color: var(--dark);
      border-bottom-right-radius: 4px;
      font-weight: 500;
    }

    .msg-time {
      font-size: 11px;
      color: var(--muted);
      margin-top: 4px;
      text-align: right;
    }

    .msg-row:not(.mine) .msg-time { text-align: left; }

    .preview-input {
      padding: 16px 24px;
      border-top: 1px solid rgba(255,255,255,0.06);
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .preview-input .fake-input {
      flex: 1;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 50px;
      padding: 12px 20px;
      font-size: 14px;
      color: var(--muted);
    }

    .preview-input .send-btn {
      width: 42px; height: 42px;
      border-radius: 50%;
      background: var(--amber);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .preview-input .send-btn svg {
      width: 18px; height: 18px;
      fill: var(--dark);
    }

    /* ── FEATURES ── */
    .features {
      position: relative;
      z-index: 1;
      padding: 80px 60px;
      text-align: center;
    }

    .section-label {
      font-size: 12px;
      letter-spacing: 4px;
      color: var(--amber);
      font-weight: 600;
      text-transform: uppercase;
      margin-bottom: 16px;
    }

    .section-title {
      font-family: 'Outfit', sans-serif;
      font-size: clamp(28px, 4vw, 42px);
      font-weight: 800;
      color: var(--text);
      margin-bottom: 60px;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 24px;
      max-width: 1000px;
      margin: 0 auto;
    }

    .feature-card {
      background: var(--navy);
      border: 1px solid rgba(245,166,35,0.1);
      border-radius: 20px;
      padding: 32px 24px;
      text-align: left;
      transition: border-color 0.2s, transform 0.2s;
    }

    .feature-card:hover {
      border-color: rgba(245,166,35,0.4);
      transform: translateY(-4px);
    }

    .feature-icon {
      width: 48px; height: 48px;
      border-radius: 14px;
      background: rgba(245,166,35,0.12);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      margin-bottom: 16px;
    }

    .feature-card h3 {
      font-family: 'Outfit', sans-serif;
      font-size: 17px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 8px;
    }

    .feature-card p {
      font-size: 14px;
      color: var(--muted);
      line-height: 1.7;
    }

    /* ── FOOTER ── */
    footer {
      position: relative;
      z-index: 1;
      border-top: 1px solid rgba(245,166,35,0.1);
      padding: 40px 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
    }

    footer .logo-text {
      font-family: 'Outfit', sans-serif;
      font-size: 20px;
      font-weight: 900;
      letter-spacing: 5px;
      color: var(--text);
    }

    footer p {
      font-size: 13px;
      color: var(--muted);
    }

    footer span { color: var(--amber); }
  </style>
</head>
<body>

<div class="hex-bg"></div>

<!-- NAVBAR -->
<nav>
  <a href="/hive/index.php" class="nav-logo">
    <svg width="32" height="36" viewBox="0 0 160 180">
      <polygon points="80,0 155,43 155,137 80,180 5,137 5,43" fill="#1A1430" stroke="#F5A623" stroke-width="6"/>
      <rect x="38" y="56" width="84" height="52" rx="13" fill="#F5A623"/>
      <polygon points="52,108 38,132 70,108" fill="#F5A623"/>
      <circle cx="59" cy="82" r="7" fill="#1A1430"/>
      <circle cx="80" cy="82" r="7" fill="#1A1430"/>
      <circle cx="101" cy="82" r="7" fill="#1A1430"/>
    </svg>
    <span>HIVE</span>
  </a>
  <ul class="nav-links">
    <li><a href="#features">Features</a></li>
    <li><a href="/hive/login.php">Login</a></li>
    <li><a href="/hive/register.php" class="nav-cta">Join the Hive</a></li>
  </ul>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-badge">Live Now — Join the Hive</div>
  <h1>HI<span>V</span>E</h1>
  <p class="hero-tagline">Your Community. Your Conversation.</p>
  <p class="hero-desc">
    A modern group chat platform built for real communities.
    Create rooms, share moments, and stay connected with your hive.
  </p>
  <div class="hero-buttons">
    <a href="/hive/register.php" class="btn-primary">Get Started Free</a>
    <a href="#features" class="btn-ghost">See Features</a>
  </div>
</section>

<!-- CHAT PREVIEW -->
<div class="preview-wrap">
  <div class="chat-preview">
    <div class="preview-header">
      <div class="room-icon">🐝</div>
      <div class="room-info">
        <h4>IIT14 Group</h4>
        <p>● 3 members online</p>
      </div>
    </div>
    <div class="preview-messages">
      <div class="msg-row">
        <div class="msg-avatar" style="background:#7F77DD;color:#fff;">RK</div>
        <div>
          <div class="msg-bubble">Hey everyone! Project meeting at 3pm today 📌</div>
          <div class="msg-time">2:45 PM ✓✓</div>
        </div>
      </div>
      <div class="msg-row">
        <div class="msg-avatar" style="background:#C29BF5;color:#2A1F40;">MK</div>
        <div>
          <div class="msg-bubble">Sure! I'll share the slides before that 🙌</div>
          <div class="msg-time">2:47 PM ✓✓</div>
        </div>
      </div>
      <div class="msg-row mine">
        <div class="msg-avatar" style="background:#F5A623;color:#0E0B1A;">SJ</div>
        <div>
          <div class="msg-bubble">Confirmed! See you all there 🔥</div>
          <div class="msg-time">2:48 PM ✓✓ 👁</div>
        </div>
      </div>
    </div>
    <div class="preview-input">
      <div class="fake-input">Type a message...</div>
      <div class="send-btn">
        <svg viewBox="0 0 24 24"><path d="M2 21l21-9L2 3v7l15 2-15 2v7z"/></svg>
      </div>
    </div>
  </div>
</div>

<!-- FEATURES -->
<section class="features" id="features">
  <p class="section-label">Why HIVE?</p>
  <h2 class="section-title">Everything your community needs</h2>
  <div class="features-grid">
    <div class="feature-card">
      <div class="feature-icon">💬</div>
      <h3>Group Rooms</h3>
      <p>Create unlimited rooms for any topic. Customise name, description and profile picture.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">⚡</div>
      <h3>Live Updates</h3>
      <p>Messages appear in real time without refreshing the page. Always stay in the flow.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">🟢</div>
      <h3>Online Status</h3>
      <p>See who is online, away or busy. Last seen timestamps keep you informed.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">😊</div>
      <h3>Reactions</h3>
      <p>React to any message with emoji. Reply directly to specific messages in the thread.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">🖼️</div>
      <h3>Image Sharing</h3>
      <p>Share images directly in the chat. Upload your profile picture and group photo.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">🌙</div>
      <h3>Dark Mode</h3>
      <p>Beautiful dark theme by default. Easy on the eyes for long conversations.</p>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="logo-text">HIVE</div>
  <p>Built with 🐝 by <span> sajeela</span> A personal full-stack practice project.</p>
  <p>PHP · MySQL · JavaScript · CSS</p>
</footer>

</body>
</html>