<?php
// =============================================
// user_dashboard.php - Regular User Dashboard
// =============================================

require_once 'functions.php';
session_start();
requireLogin(); // Prevent direct URL access

$user_id = (int)$_SESSION['user_id'];
$user = getUserById($user_id);
$message = '';
$message_type = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Update profile
    if ($action === 'update_profile') {
        $data = [
            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
            'last_name'  => sanitizeInput($_POST['last_name'] ?? ''),
            'email'      => sanitizeInput($_POST['email'] ?? ''),
            'gender'     => sanitizeInput($_POST['gender'] ?? ''),
            'address'    => sanitizeInput($_POST['address'] ?? '')
        ];

        // Validate profile fields
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
            $errors[] = "First name, last name, and email are required.";
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }

        if (empty($errors)) {
            if (updateProfile($user_id, $data)) {
                $_SESSION['user_name'] = $data['first_name'] . ' ' . $data['last_name'];
                $message = "Profile updated successfully!";
                $message_type = 'success';
                $user = getUserById($user_id); // Refresh user data
            } else {
                $message = "Failed to update profile.";
                $message_type = 'error';
            }
        } else {
            $message_type = 'error';
        }

    // Change password
    } elseif ($action === 'change_password') {
        $current  = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_new_password'] ?? '';

        if (empty($current) || empty($new_pass) || empty($confirm)) {
            $errors[] = "All password fields are required.";
        } elseif ($new_pass !== $confirm) {
            $errors[] = "New passwords do not match.";
        } elseif (strlen($new_pass) < 8) {
            $errors[] = "New password must be at least 8 characters.";
        }

        if (empty($errors)) {
            $result = changePassword($user_id, $current, $new_pass);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } else {
            $message_type = 'error';
        }

    // Delete account
    } elseif ($action === 'delete_account') {
        $confirm_delete = $_POST['confirm_delete'] ?? '';
        if ($confirm_delete === 'DELETE') {
            deleteUser($user_id);
            session_destroy();
            header("Location: login.php?deleted=1");
            exit();
        } else {
            $message = "Please type DELETE to confirm account deletion.";
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Dashboard - User Management</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: #ccd6f6; min-height: 100vh; }
        .navbar { background: #16213e; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #00b894; }
        .navbar h1 { color: #00b894; font-size: 20px; }
        .nav-right { display: flex; gap: 15px; align-items: center; }
        .nav-right span { color: #a8b2d8; font-size: 14px; }
        .btn-logout { background: #e94560; color: white; padding: 8px 18px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; font-weight: 600; }
        .content { padding: 30px; max-width: 900px; margin: 0 auto; }
        .tabs { display: flex; gap: 5px; margin-bottom: 25px; border-bottom: 2px solid #0f3460; }
        .tab-btn { padding: 10px 20px; background: none; border: none; color: #8892b0; cursor: pointer; font-size: 14px; font-weight: 600; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.3s; }
        .tab-btn.active { color: #00b894; border-bottom-color: #00b894; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
        .card { background: #16213e; border-radius: 12px; border: 1px solid #0f3460; padding: 30px; }
        .card h2 { color: #00b894; margin-bottom: 20px; font-size: 18px; }
        .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #00b89420; border: 1px solid #00b894; color: #00b894; }
        .alert-error { background: #e9456020; border: 1px solid #e94560; color: #e94560; }
        .profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #0f3460; }
        .avatar { width: 80px; height: 80px; background: linear-gradient(135deg, #00b894, #00cec9); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; color: white; }
        .profile-info h3 { color: #ccd6f6; font-size: 22px; }
        .profile-info p { color: #8892b0; font-size: 14px; margin-top: 4px; }
        .badge { padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; margin-top: 5px; }
        .badge-active { background: #00b89420; color: #00b894; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; color: #a8b2d8; font-size: 12px; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 11px 14px; background: #0f3460; border: 1px solid #1a4a7a; color: #ccd6f6; border-radius: 8px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #00b894; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .btn-save { padding: 12px 30px; background: linear-gradient(135deg, #00b894, #00cec9); color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 700; cursor: pointer; }
        .btn-save:hover { opacity: 0.85; }
        .btn-danger { padding: 12px 30px; background: linear-gradient(135deg, #e94560, #c0392b); color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 700; cursor: pointer; }
        .danger-zone { background: #e9456010; border: 1px solid #e9456040; border-radius: 8px; padding: 20px; margin-top: 20px; }
        .danger-zone p { color: #8892b0; font-size: 13px; margin-bottom: 15px; }
        .error-list { color: #ff6b6b; font-size: 13px; margin-bottom: 10px; }
        .error-list li { margin: 3px 0; }
        .info-table { width: 100%; }
        .info-table tr td { padding: 10px 0; border-bottom: 1px solid #0f3460; font-size: 14px; }
        .info-table tr td:first-child { color: #8892b0; width: 35%; }
    </style>
</head>
<body>

<div class="navbar">
    <h1>👤 My Dashboard</h1>
    <div class="nav-right">
        <span>Hello, <?= htmlspecialchars($_SESSION['user_name']) ?>!</span>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="content">

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul class="error-list">
                <?php foreach ($errors as $err): ?>
                    <li>⚠️ <?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('profile')">👤 My Profile</button>
        <button class="tab-btn" onclick="showTab('edit')">✏️ Edit Profile</button>
        <button class="tab-btn" onclick="showTab('password')">🔑 Change Password</button>
        <button class="tab-btn" onclick="showTab('danger')">⚠️ Delete Account</button>
    </div>

    <!-- Profile Tab -->
    <div class="tab-panel active" id="tab-profile">
        <div class="card">
            <div class="profile-header">
                <div class="avatar"><?= strtoupper(substr($user['first_name'], 0, 1)) ?></div>
                <div class="profile-info">
                    <h3><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                    <p><?= htmlspecialchars($user['email']) ?></p>
                    <span class="badge badge-active"><?= ucfirst($user['role']) ?></span>
                </div>
            </div>

            <table class="info-table">
                <tr><td>First Name</td><td><?= htmlspecialchars($user['first_name']) ?></td></tr>
                <tr><td>Last Name</td><td><?= htmlspecialchars($user['last_name']) ?></td></tr>
                <tr><td>Email</td><td><?= htmlspecialchars($user['email']) ?></td></tr>
                <tr><td>Gender</td><td><?= htmlspecialchars($user['gender']) ?></td></tr>
                <tr><td>Role</td><td><?= ucfirst($user['role']) ?></td></tr>
                <tr><td>Status</td><td><?= ucfirst($user['status']) ?></td></tr>
                <tr><td>Address</td><td><?= htmlspecialchars($user['address'] ?? 'N/A') ?></td></tr>
                <tr><td>Member Since</td><td><?= date('F d, Y', strtotime($user['created_at'])) ?></td></tr>
            </table>
        </div>
    </div>

    <!-- Edit Profile Tab -->
    <div class="tab-panel" id="tab-edit">
        <div class="card">
            <h2>✏️ Edit My Profile</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender">
                        <?php foreach (['Male','Female','Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= $user['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                </div>
                <button type="submit" class="btn-save">💾 Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Change Password Tab -->
    <div class="tab-panel" id="tab-password">
        <div class="card">
            <h2>🔑 Change Password</h2>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" placeholder="Enter current password">
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="Min. 8 characters">
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_new_password" placeholder="Repeat new password">
                </div>
                <button type="submit" class="btn-save">🔒 Update Password</button>
            </form>
        </div>
    </div>

    <!-- Delete Account Tab -->
    <div class="tab-panel" id="tab-danger">
        <div class="card">
            <h2 style="color:#e94560;">⚠️ Delete My Account</h2>
            <div class="danger-zone">
                <p>⚠️ <strong>Warning:</strong> This action is permanent and cannot be undone. All your data will be removed.</p>
                <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to permanently delete your account?')">
                    <input type="hidden" name="action" value="delete_account">
                    <div class="form-group">
                        <label>Type <strong>DELETE</strong> to confirm</label>
                        <input type="text" name="confirm_delete" placeholder="Type DELETE here">
                    </div>
                    <button type="submit" class="btn-danger">🗑️ Permanently Delete Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.target.classList.add('active');
}
</script>
</body>
</html>
