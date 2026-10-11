<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$active = 'settings';
$users = $db->query('SELECT id, username, full_name, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings — FireStrat</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div id="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main>
    <div class="topbar">
      <button class="iconbtn" id="mobileMenuBtn" title="Menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
      </button>
      <div class="page-title" style="flex:1;text-align:center;">Settings</div>
      <div class="topbar-right">
        <button class="iconbtn" id="themeBtn" title="Toggle dark mode">
          <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg>
        </button>
      </div>
    </div>
 <!-- USER -->
   <div class="panel" style="margin-top:16px;">
  <div class="panel-head">
    <h3>Add new officer</h3>
  </div>
  <div style="padding:20px 24px;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

      <div style="display:flex;flex-direction:column;gap:6px;">
        <label style="font-size:13px;color:var(--ink-dim);font-weight:500;">Full name</label>
        <input type="text" id="newFullName" placeholder="e.g. Juan dela Cruz"
          style="padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--panel);color:var(--ink);font-size:14px;outline:none;width:100%;box-sizing:border-box;">
      </div>

      <div style="display:flex;flex-direction:column;gap:6px;">
        <label style="font-size:13px;color:var(--ink-dim);font-weight:500;">Username</label>
        <input type="text" id="newUsername" placeholder="e.g. jdelacruz"
          style="padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--panel);color:var(--ink);font-size:14px;outline:none;width:100%;box-sizing:border-box;">
      </div>

      <div style="display:flex;flex-direction:column;gap:6px;">
        <label style="font-size:13px;color:var(--ink-dim);font-weight:500;">Password</label>
        <input type="password" id="newPassword" placeholder="Min. 8 characters"
          style="padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--panel);color:var(--ink);font-size:14px;outline:none;width:100%;box-sizing:border-box;">
      </div>

      <div style="display:flex;flex-direction:column;gap:6px;">
        <label style="font-size:13px;color:var(--ink-dim);font-weight:500;">Role</label>
        <select id="newRole"
          style="padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--panel);color:var(--ink);font-size:14px;outline:none;width:100%;box-sizing:border-box;">
          <option value="fire_marshal">Fire Marshal</option>
          <option value="admin">Admin</option>
          <option value="responder">Responder</option>
          <option value="officer">Officer</option>
        </select>
      </div>

    </div>

    <div id="userMsg" class="form-msg" style="margin-top:10px;font-size:13px;"></div>

    <!-- create account button for officers -->
    <button class="comm-btn"
      style="margin-top:16px;padding:10px 24px;width:auto;font-size:14px;font-weight:600;border-radius:8px;background:var(--ember);border-color:var(--ember);"
      onclick="createUser()">
      Create account
    </button>

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

              <!-- delete button for officers -->
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