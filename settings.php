<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();
requireRole(['admin']);

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
          <!-- <option value="responder">Responder</option> -->
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
              <button class="iconbtn" style="width:auto;padding:0 10px;font-size:11px;background:var(--blue);border-color:var(--blue);color:#fff;margin-right:6px;"
                onclick="openEditModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['full_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['role'], ENT_QUOTES) ?>')">Edit</button>
              <button class="iconbtn" style="width:auto;padding:0 10px;font-size:11px;background:var(--ember);" onclick="deleteUser(<?= $u['id'] ?>)">Delete</button>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </main>
</div>

<!-- Edit Officer Modal -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:28px;width:420px;max-width:95vw;box-shadow:var(--shadow);">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
      <h3 style="margin:0;font-family:'Oswald';font-size:16px;">Edit Officer</h3>
      <button onclick="closeEditModal()" style="background:none;border:none;color:var(--ink-dim);cursor:pointer;font-size:20px;line-height:1;">&times;</button>
    </div>

    <input type="hidden" id="editUserId">

    <div style="display:flex;flex-direction:column;gap:14px;">
      <div style="display:flex;flex-direction:column;gap:6px;">
        <label style="font-size:12px;color:var(--ink-dim);font-weight:500;">Full name</label>
        <input type="text" id="editFullName"
          style="padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--panel-2);color:var(--ink);font-size:14px;outline:none;">
      </div>

      <div style="display:flex;flex-direction:column;gap:6px;">
        <label style="font-size:12px;color:var(--ink-dim);font-weight:500;">Username</label>
        <input type="text" id="editUsername"
          style="padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--panel-2);color:var(--ink);font-size:14px;outline:none;">
      </div>

      <div style="display:flex;flex-direction:column;gap:6px;">
        <label style="font-size:12px;color:var(--ink-dim);font-weight:500;">New password <span style="color:var(--ink-dim);font-weight:400;">(leave blank to keep current)</span></label>
        <div style="position:relative;">
          <input type="password" id="editPassword" placeholder="Min. 8 characters"
            style="padding:10px 40px 10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--panel-2);color:var(--ink);font-size:14px;outline:none;width:100%;box-sizing:border-box;">
          <button onclick="toggleEditPw()" title="Show/hide" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--ink-dim);font-size:13px;" id="pwToggleBtn">Show</button>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:6px;">
        <label style="font-size:12px;color:var(--ink-dim);font-weight:500;">Role</label>
        <select id="editRole"
          style="padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--panel-2);color:var(--ink);font-size:14px;outline:none;">
          <option value="fire_marshal">Fire Marshal</option>
          <option value="admin">Admin</option>
          <option value="officer">Officer</option>
        </select>
      </div>
    </div>

    <div id="editMsg" style="margin-top:12px;font-size:13px;display:none;padding:8px 10px;border-radius:7px;"></div>

    <div style="display:flex;gap:10px;margin-top:20px;justify-content:flex-end;">
      <button onclick="closeEditModal()"
        style="padding:9px 20px;border-radius:8px;border:1px solid var(--line);background:transparent;color:var(--ink);font-size:13px;cursor:pointer;">Cancel</button>
      <button onclick="saveEdit()"
        style="padding:9px 20px;border-radius:8px;border:none;background:var(--blue);color:#fff;font-size:13px;font-weight:600;cursor:pointer;">Save changes</button>
    </div>
  </div>
</div>

<script src="assets/js/app.js"></script>

<script>
function openEditModal(id, fullName, username, role) {
  document.getElementById('editUserId').value = id;
  document.getElementById('editFullName').value = fullName;
  document.getElementById('editUsername').value = username;
  document.getElementById('editPassword').value = '';
  document.getElementById('editRole').value = role;
  document.getElementById('editMsg').style.display = 'none';
  const modal = document.getElementById('editModal');
  modal.style.display = 'flex';
}

function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
}

function toggleEditPw() {
  const input = document.getElementById('editPassword');
  const btn = document.getElementById('pwToggleBtn');
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = 'Hide';
  } else {
    input.type = 'password';
    btn.textContent = 'Show';
  }
}

async function saveEdit() {
  const id       = document.getElementById('editUserId').value;
  const fullName = document.getElementById('editFullName').value.trim();
  const username = document.getElementById('editUsername').value.trim();
  const password = document.getElementById('editPassword').value;
  const role     = document.getElementById('editRole').value;
  const msg      = document.getElementById('editMsg');

  if (!fullName || !username) {
    msg.textContent = 'Full name and username are required.';
    msg.style.cssText = 'display:block;background:var(--ember-dim);color:var(--ember);padding:8px 10px;border-radius:7px;font-size:13px;';
    return;
  }
  if (password && password.length < 8) {
    msg.textContent = 'Password must be at least 8 characters.';
    msg.style.cssText = 'display:block;background:var(--ember-dim);color:var(--ember);padding:8px 10px;border-radius:7px;font-size:13px;';
    return;
  }

  const res = await fetch('api/update_user.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, full_name: fullName, username, password, role })
  });
  const data = await res.json();

  if (data.ok) {
    msg.textContent = 'Saved!';
    msg.style.cssText = 'display:block;background:var(--green-dim);color:var(--green);padding:8px 10px;border-radius:7px;font-size:13px;';
    setTimeout(() => { closeEditModal(); location.reload(); }, 800);
  } else {
    msg.textContent = data.error || 'Something went wrong.';
    msg.style.cssText = 'display:block;background:var(--ember-dim);color:var(--ember);padding:8px 10px;border-radius:7px;font-size:13px;';
  }
}
</script>
</body>
</html>