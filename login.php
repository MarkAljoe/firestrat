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
</head>
<body data-theme="dark" class="auth-body">
  <div class="auth-card">
    <div class="brand" style="justify-content:center;padding-bottom:22px;">
      <div class="mark"></div>
      <div class="brand-text">
        <div class="title">FireStrat</div>
        <div class="sub">BFP Virac, Catanduanes</div>
      </div>
    </div>
    <?php if ($error): ?>
      <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" class="report-form">
      <label>Username</label>
      <input type="text" name="username" autofocus required>
      <label>Password</label>
      <input type="password" name="password" required>
      <button type="submit">Sign in</button>
    </form>
    <p class="auth-hint">Demo account — username <code>fmarshal</code> / password <code>firestrat123</code></p>
    <p class="auth-hint"><a href="community.php">Continue as community member →</a></p>
  </div>
</body>
</html>
