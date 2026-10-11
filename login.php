<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both a username and password.';
    } else {
        $stmt = getDB()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            header('Location: index.php');
            exit;
        }
        $error = 'Incorrect username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in — FireStrat</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #111;
    font-family: 'Inter', sans-serif;
  }

  .login-wrap {
    display: flex;
    width: 820px;
    max-width: 96vw;
    min-height: 480px;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,0.5);
  }

  /* ── Left panel ── */
  .login-left {
    width: 340px;
    flex-shrink: 0;
    background: #1a0a0a;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 32px;
    gap: 24px;
    position: relative;
    overflow: hidden;
  }
  .login-left::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 60% 30%, rgba(180,30,10,0.35) 0%, transparent 70%);
  }
  .login-left img {
    width: 120px;
    height: 120px;
    object-fit: contain;
    position: relative;
    z-index: 1;
    filter: drop-shadow(0 4px 24px rgba(200,40,10,0.4));
  }
  .login-left-text {
    position: relative;
    z-index: 1;
    text-align: center;
  }
  .login-left-text h1 {
    font-family: 'Oswald', sans-serif;
    font-size: 28px;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.02em;
  }
  .login-left-text p {
    font-size: 12px;
    color: rgba(255,255,255,0.5);
    margin-top: 4px;
    letter-spacing: 0.03em;
  }
  .login-tagline {
    position: relative;
    z-index: 1;
    font-size: 11px;
    color: rgba(255,255,255,0.3);
    text-align: center;
    font-style: italic;
    letter-spacing: 0.04em;
  }

  /* ── Right panel ── */
  .login-right {
    flex: 1;
    background: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 48px 44px;
  }
  .login-right h2 {
    font-family: 'Oswald', sans-serif;
    font-size: 24px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 6px;
  }
  .login-right .login-sub {
    font-size: 13px;
    color: #888;
    margin-bottom: 32px;
  }

  .alert-error {
    background: #fff0ee;
    border: 1px solid #f5c0b8;
    color: #c0392b;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 13px;
    margin-bottom: 18px;
  }

  .field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
  }
  .field label {
    font-size: 12px;
    font-weight: 600;
    color: #555;
    letter-spacing: 0.03em;
    text-transform: uppercase;
  }
  .field input {
    border: none;
    border-bottom: 1.5px solid #ddd;
    padding: 10px 0;
    font-size: 14px;
    color: #1a1a1a;
    font-family: 'Inter', sans-serif;
    background: transparent;
    transition: border-color 0.15s;
    outline: none;
    width: 100%;
  }
  .field input:focus {
    border-bottom-color: #c0392b;
  }

  .btn-signin {
    width: 100%;
    padding: 13px;
    background: #c0392b;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    font-family: 'Oswald', sans-serif;
    letter-spacing: 0.06em;
    cursor: pointer;
    margin-top: 8px;
    transition: opacity 0.15s;
  }
  .btn-signin:hover { opacity: 0.88; }

  .login-hints {
    margin-top: 20px;
    font-size: 12px;
    color: #aaa;
    text-align: center;
    line-height: 1.8;
  }
  .login-hints code {
    background: #f5f5f5;
    padding: 1px 5px;
    border-radius: 4px;
    color: #555;
    font-size: 11px;
  }
  .login-hints a {
    color: #c0392b;
    text-decoration: none;
  }
  .login-hints a:hover { text-decoration: underline; }

  @media (max-width: 620px) {
    .login-wrap { flex-direction: column; width: 100%; min-height: unset; border-radius: 0; }
    .login-left { width: 100%; padding: 32px 24px; flex-direction: row; justify-content: center; gap: 16px; }
    .login-left img { width: 60px; height: 60px; }
    .login-left-text h1 { font-size: 20px; }
    .login-tagline { display: none; }
    .login-right { padding: 32px 24px; }
  }
</style>
</head>
<body>
  <div class="login-wrap">

    <!-- Left: branding -->
    <div class="login-left">
      <img src="assets/img/logo.png" alt="FireStrat Logo">
      <div class="login-left-text">
        <h1>FireStrat</h1>
        <p>BFP Virac · Catanduanes</p>
      </div>
      <div class="login-tagline">Map the risk. Lead the response.</div>
    </div>

    <!-- Right: form -->
    <div class="login-right">
      <h2>Welcome back</h2>
      <p class="login-sub">Sign in to your FireStrat account.</p>

      <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="field">
          <label>Username</label>
          <input type="text" name="username" autofocus required>
        </div>
        <div class="field">
          <label>Password</label>
          <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn-signin">Sign in</button>
      </form>

      <div class="login-hints">
        <!-- Demo account — username <code>fmarshal</code> / password <code>firestrat123</code><br> -->
        <a href="community.php">Continue as community member →</a>
      </div>
    </div>

  </div>
</body>
</html>