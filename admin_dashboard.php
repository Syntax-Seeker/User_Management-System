<?php
// =============================================
// admin_dashboard.php - Admin CRUD Dashboard
// =============================================

require_once 'functions.php';
session_start();
requireAdmin(); // Prevent direct access; must be admin

$message = '';
$message_type = '';
$search = sanitizeInput($_GET['search'] ?? '');

// Handle Actions via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $uid = (int)$_POST['user_id'];
        if ($uid !== (int)$_SESSION['user_id']) {
            deleteUser($uid);
            $message = "User deleted successfully.";
            $message_type = 'success';
        } else {
            $message = "You cannot delete your own account here.";
            $message_type = 'error';
        }

    } elseif ($action === 'toggle_status') {
        $uid = (int)$_POST['user_id'];
        $current_status = $_POST['current_status'];
        $result = toggleUserStatus($uid, $current_status);
        $message = "User status updated to " . $result['new_status'] . ".";
        $message_type = 'success';

    } elseif ($action === 'update_user') {
        $uid = (int)$_POST['user_id'];
        $data = [
            'first_name' => sanitizeInput($_POST['first_name']),
            'last_name'  => sanitizeInput($_POST['last_name']),
            'email'      => sanitizeInput($_POST['email']),
            'gender'     => sanitizeInput($_POST['gender']),
            'role'       => sanitizeInput($_POST['role']),
            'status'     => sanitizeInput($_POST['status']),
            'address'    => sanitizeInput($_POST['address'])
        ];
        updateUser($uid, $data);
        $message = "User updated successfully.";
        $message_type = 'success';
    }
}

// Fetch edit target
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_user = getUserById((int)$_GET['edit']);
}

// Get all users (with optional search)
$users = getAllUsers($search);
$total_users = count($users);

// Gather stats using for loop
$active_count = 0;
$admin_count = 0;
for ($i = 0; $i < $total_users; $i++) {
    if ($users[$i]['status'] === 'active') $active_count++;
    if ($users[$i]['role'] === 'admin') $admin_count++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - User Management</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: #ccd6f6; min-height: 100vh; }
        .navbar { background: #16213e; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e94560; }
        .navbar h1 { color: #e94560; font-size: 20px; }
        .nav-right { display: flex; gap: 15px; align-items: center; }
        .nav-right span { color: #a8b2d8; font-size: 14px; }
        .btn-logout { background: #e94560; color: white; padding: 8px 18px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; font-weight: 600; }
        .content { padding: 30px; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #16213e; padding: 20px; border-radius: 12px; border: 1px solid #0f3460; text-align: center; }
        .stat-card .number { font-size: 36px; font-weight: 700; color: #e94560; }
        .stat-card .label { color: #8892b0; font-size: 13px; margin-top: 5px; }
        .card { background: #16213e; border-radius: 12px; border: 1px solid #0f3460; padding: 25px; margin-bottom: 25px; }
        .card h2 { color: #e94560; margin-bottom: 20px; font-size: 18px; }
        .search-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-bar input { flex: 1; padding: 10px 15px; background: #0f3460; border: 1px solid #1a4a7a; color: #ccd6f6; border-radius: 8px; font-size: 14px; }
        .search-bar input:focus { outline: none; border-color: #e94560; }
        .search-bar button { padding: 10px 20px; background: #e94560; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .search-bar a { padding: 10px 16px; background: #0f3460; color: #a8b2d8; border-radius: 8px; text-decoration: none; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0f3460; color: #a8b2d8; padding: 12px 15px; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 12px 15px; border-bottom: 1px solid #0f3460; font-size: 14px; vertical-align: middle; }
        tr:hover td { background: #0f346020; }
        .badge { padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-active { background: #00b89420; color: #00b894; }
        .badge-inactive { background: #e9456020; color: #e94560; }
        .badge-admin { background: #6c63ff20; color: #6c63ff; }
        .badge-user { background: #fdcb6e20; color: #fdcb6e; }
        .action-btns { display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-edit { background: #6c63ff20; color: #6c63ff; border: 1px solid #6c63ff; padding: 5px 12px; border-radius: 5px; text-decoration: none; font-size: 12px; font-weight: 600; }
        .btn-delete { background: #e9456020; color: #e94560; border: 1px solid #e94560; padding: 5px 12px; border-radius: 5px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .btn-toggle { padding: 5px 12px; border-radius: 5px; font-size: 12px; font-weight: 600; cursor: pointer; border: none; }
        .btn-activate { background: #00b89420; color: #00b894; border: 1px solid #00b894; }
        .btn-deactivate { background: #fdcb6e20; color: #fdcb6e; border: 1px solid #fdcb6e; }
        .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #00b89420; border: 1px solid #00b894; color: #00b894; }
        .alert-error { background: #e9456020; border: 1px solid #e94560; color: #e94560; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: #16213e; border: 1px solid #0f3460; border-radius: 16px; padding: 30px; width: 100%; max-width: 500px; }
        .modal h3 { color: #e94560; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; color: #a8b2d8; font-size: 12px; margin-bottom: 5px; font-weight: 600; text-transform: uppercase; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; background: #0f3460; border: 1px solid #1a4a7a; color: #ccd6f6; border-radius: 6px; font-size: 14px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .modal-actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn-save { flex: 1; padding: 11px; background: #e94560; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .btn-cancel { flex: 1; padding: 11px; background: #0f3460; color: #a8b2d8; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .no-data { text-align: center; color: #8892b0; padding: 40px; }
    </style>
</head>
<body>

<div class="navbar">
    <h1>🛡️ Admin Dashboard</h1>
    <div class="nav-right">
        <span>👤 <?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="content">

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="stats">
        <div class="stat-card">
            <div class="number"><?= $total_users ?></div>
            <div class="label">Total Users</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $active_count ?></div>
            <div class="label">Active Users</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $total_users - $active_count ?></div>
            <div class="label">Inactive Users</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $admin_count ?></div>
            <div class="label">Admins</div>
        </div>
    </div>

    <!-- User Table -->
    <div class="card">
        <h2>👥 Manage Users</h2>

        <!-- Search Bar -->
        <form method="GET" action="admin_dashboard.php">
            <div class="search-bar">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email...">
                <button type="submit">🔍 Search</button>
                <?php if (!empty($search)): ?>
                    <a href="admin_dashboard.php">✕ Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!empty($search)): ?>
            <p style="color:#8892b0;font-size:13px;margin-bottom:15px;">Showing <?= $total_users ?> result(s) for "<strong><?= htmlspecialchars($search) ?></strong>"</p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Gender</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="8" class="no-data">No users found.</td></tr>
                <?php else: ?>
                    <?php
                    // FOREACH LOOP to display all users
                    foreach ($users as $index => $u):
                    ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['gender']) ?></td>
                        <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                        <td><span class="badge badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
                        <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="?edit=<?= $u['id'] ?>" class="btn-edit">✏️ Edit</a>

                                <!-- Toggle Status -->
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="current_status" value="<?= $u['status'] ?>">
                                    <button type="submit" class="btn-toggle <?= $u['status'] === 'active' ? 'btn-deactivate' : 'btn-activate' ?>">
                                        <?= $u['status'] === 'active' ? '🔒 Deactivate' : '🔓 Activate' ?>
                                    </button>
                                </form>

                                <!-- Delete with confirmation -->
                                <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline" onsubmit="return confirm('⚠️ Are you sure you want to delete <?= htmlspecialchars(addslashes($u['first_name'])) ?>? This cannot be undone.')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn-delete">🗑️ Delete</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<?php if ($edit_user): ?>
<div class="modal-overlay active" id="editModal">
    <div class="modal">
        <h3>✏️ Edit User: <?= htmlspecialchars($edit_user['first_name'] . ' ' . $edit_user['last_name']) ?></h3>
        <form method="POST" action="admin_dashboard.php">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" value="<?= $edit_user['id'] ?>">

            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($edit_user['first_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($edit_user['last_name']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($edit_user['email']) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <?php foreach (['Male','Female','Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= $edit_user['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="user" <?= $edit_user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= $edit_user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="active" <?= $edit_user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $edit_user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($edit_user['address'] ?? '') ?>">
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn-save">💾 Save Changes</button>
                <a href="admin_dashboard.php" class="btn-cancel" style="text-align:center;text-decoration:none;display:block;">✕ Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

</body>
</html>
