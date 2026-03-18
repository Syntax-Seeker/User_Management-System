<?php
// =============================================
// login.php - User Login Module
// =============================================

require_once 'functions.php';
session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: user_dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic empty check using logical operator &&
    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } else {
        // Retrieve user from database
        $user = getUserByEmail($email);

        // Nested if statements with logical operators
        if (!$user) {
            // Email does not exist
            $error = "No account found with that email address.";
        } else {
            if ($user['status'] === 'inactive') {
                // Account is blocked
                $error = "Your account has been deactivated. Please contact the administrator.";
            } else {
                if (!password_verify($password, $user['password'])) {
                    // Wrong password → increment attempts
                    incrementLoginAttempts($email);

                    // Re-fetch to get updated attempts
                    $updated_user = getUserByEmail($email);
                    $attempts_left = 3 - $updated_user['login_attempts'];

                    if ($updated_user['status'] === 'inactive') {
                        $error = "Too many failed attempts. Your account has been locked. Contact the administrator.";
                    } elseif ($attempts_left > 0) {
                        $error = "Incorrect password. You have $attempts_left attempt(s) remaining.";
                    } else {
                        $error = "Account locked due to too many failed attempts.";
                    }
                } else {
                    // ✅ Login successful
                    resetLoginAttempts($email);

                    // Store session variables
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['role']      = $user['role'];

                    // Conditional redirect based on role
                    if ($user['role'] === 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: user_dashboard.php");
                    }
                    exit();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - User Management System</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { background: #16213e; padding: 50px 40px; border-radius: 16px; width: 100%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); border: 1px solid #0f3460; }
        .logo { text-align: center; font-size: 50px; margin-bottom: 10px; }
        h2 { color: #e94560; text-align: center; margin-bottom: 30px; font-size: 26px; }
        .alert-error { background: #ff000020; border: 1px solid #e94560; color: #ff6b6b; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; color: #a8b2d8; font-size: 13px; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        input { width: 100%; padding: 13px 15px; background: #0f3460; border: 1px solid #1a4a7a; color: #ccd6f6; border-radius: 8px; font-size: 15px; transition: border 0.3s; }
        input:focus { outline: none; border-color: #e94560; }
        .btn { width: 100%; padding: 13px; background: linear-gradient(135deg, #e94560, #c0392b); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 700; cursor: pointer; margin-top: 5px; transition: opacity 0.3s; }
        .btn:hover { opacity: 0.85; }
        .register-link { text-align: center; margin-top: 20px; color: #8892b0; font-size: 14px; }
        .register-link a { color: #e94560; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">🔐</div>
    <h2>Welcome Back</h2>

    <?php if (!empty($error)): ?>
        <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com" autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Your password">
        </div>
        <button type="submit" class="btn">Login</button>
    </form>

    <div class="register-link">Don't have an account? <a href="register.php">Register here</a></div>
</div>
</body>
</html>
