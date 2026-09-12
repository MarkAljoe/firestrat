<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$active = 'users';
$users = $db->query('SELECT id, username, full_name, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users — FireStrat</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div id="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main>
    <div class="topbar">
      <div class="page-title">Users</div>
      <div class="topbar-right">
        <button class="iconbtn" id="themeBtn" title="Toggle dark mode">
          <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg>
        </button>
      </div>
    </div>

    <div class="panel" style="margin-top:16px;">
      <div class="panel-head">
        <h3>Add new officer</h3>
      </div>
      <div style="padding:16px 18px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
          <div><label>Full name</label><input type="text" id="newFullName"></div>
          <div><label>Username</label><input type="text" id="newUsername"></div>
          <div><label>Password</label><input type="password" id="newPassword"></div>
          <div><label>Role</label>
            <select id="newRole">
              <option value="fire_marshal">Fire Marshal</option>
              <option value="admin">Admin</option>
              <option value="responder">Responder</option>
            </select>
          </div>
        </div>
        <div id="userMsg" class="form-msg"></div>
        <button class="comm-btn" style="width:auto;padding:8px 16px;margin-top:12px;" onclick="createUser()">Create account</button>
      </div>
    </div>

    <div class="panel" style="margin-top:16px;">
      <div class="panel-head">
        <h3>All officers</h3>
      </div>
      <div style="padding:8px 18px;">
        <?php foreach ($users as $u): ?>
          <div class="comm-zone" style="align-items:center;">
            <div style="flex:1;">
              <div class="zn"><?= htmlspecialchars($u['full_name']) ?></div>
              <div class="zd">@<?= htmlspecialchars($u['username']) ?> · <?= htmlspecialchars($u['role']) ?></div>
            </div>
            <?php if ($u['username'] !== ($_SESSION['username'] ?? '')): ?>
              <button class="iconbtn" style="width:auto;padding:0 10px;font-size:11px;background:var(--ember);" onclick="deleteUser(<?= $u['id'] ?>)">Delete</button>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </main>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>