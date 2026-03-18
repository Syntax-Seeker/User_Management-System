<?php
// =============================================
// register.php - User Registration Module
// =============================================

require_once 'functions.php';

$errors = [];
$success = '';
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Store all inputs in PHP variables and sanitize
    $first_name       = sanitizeInput($_POST['first_name'] ?? '');
    $last_name        = sanitizeInput($_POST['last_name'] ?? '');
    $email            = sanitizeInput($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $gender           = sanitizeInput($_POST['gender'] ?? '');
    $role             = sanitizeInput($_POST['role'] ?? '');
    $address          = sanitizeInput($_POST['address'] ?? '');

    // Store in associative array for validation
    $form_data = [
        'first_name'       => $first_name,
        'last_name'        => $last_name,
        'email'            => $email,
        'password'         => $password,
        'confirm_password' => $confirm_password,
        'gender'           => $gender,
        'role'             => $role,
        'address'          => $address
    ];

    // Validate inputs
    $errors = validateRegistration($form_data);

    // If no validation errors, try to register
    if (empty($errors)) {
        $result = registerUser($form_data);
        if ($result['success']) {
            $success = $result['message'];
            $form_data = []; // Clear form on success
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - User Management System</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: #16213e; padding: 40px; border-radius: 16px; width: 100%; max-width: 560px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); border: 1px solid #0f3460; }
        h2 { color: #e94560; text-align: center; margin-bottom: 30px; font-size: 28px; }
        .alert-error { background: #ff000020; border: 1px solid #e94560; color: #ff6b6b; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-error ul { margin: 0; padding-left: 20px; }
        .alert-error li { margin: 4px 0; font-size: 14px; }
        .alert-success { background: #00ff0020; border: 1px solid #00b894; color: #00b894; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .form-group { margin-bottom: 18px; }
        label { display: block; color: #a8b2d8; font-size: 13px; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        input, select, textarea { width: 100%; padding: 12px 15px; background: #0f3460; border: 1px solid #1a4a7a; color: #ccd6f6; border-radius: 8px; font-size: 15px; transition: border 0.3s; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #e94560; }
        select option { background: #0f3460; }
        textarea { height: 80px; resize: vertical; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .btn { width: 100%; padding: 13px; background: linear-gradient(135deg, #e94560, #c0392b); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 700; cursor: pointer; margin-top: 10px; transition: opacity 0.3s; }
        .btn:hover { opacity: 0.85; }
        .login-link { text-align: center; margin-top: 20px; color: #8892b0; font-size: 14px; }
        .login-link a { color: #e94560; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h2>📋 Register</h2>

    <?php if (!empty($success)): ?>
        <div class="alert-success">✅ <?= htmlspecialchars($success) ?> <a href="login.php" style="color:#00b894;font-weight:700;">Login here</a></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <strong>⚠️ Please fix the following errors:</strong>
            <ul>
                <?php
                // Display errors using a FOR loop (demonstrating for loop usage)
                for ($i = 0; $i < count($errors); $i++) {
                    echo "<li>" . htmlspecialchars($errors[$i]) . "</li>";
                }
                ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <div class="row">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($form_data['first_name'] ?? '') ?>" placeholder="John">
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($form_data['last_name'] ?? '') ?>" placeholder="Doe">
            </div>
        </div>

        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" placeholder="john@example.com">
        </div>

        <div class="row">
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Min. 8 characters">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Repeat password">
            </div>
        </div>

        <div class="row">
            <div class="form-group">
                <label>Gender</label>
                <select name="gender">
                    <option value="">-- Select --</option>
                    <?php
                    $genders = ['Male', 'Female', 'Other'];
                    // Foreach loop for gender options
                    foreach ($genders as $g) {
                        $selected = (($form_data['gender'] ?? '') === $g) ? 'selected' : '';
                        echo "<option value=\"$g\" $selected>$g</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <option value="">-- Select --</option>
                    <option value="user" <?= (($form_data['role'] ?? '') === 'user') ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= (($form_data['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Address</label>
            <textarea name="address" placeholder="Enter your full address"><?= htmlspecialchars($form_data['address'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn">Create Account</button>
    </form>

    <div class="login-link">Already have an account? <a href="login.php">Login here</a></div>
</div>
</body>
</html>
