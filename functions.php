<?php
// =============================================
// functions.php - Reusable Helper Functions
// =============================================

require_once 'config.php';

// ----------------------------
// INPUT SANITIZATION
// ----------------------------
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// ----------------------------
// VALIDATION FUNCTIONS
// ----------------------------
function validateRegistration($data) {
    $errors = [];  // Associative array to collect errors

    // Required fields check using foreach loop
    $required_fields = [
        'first_name'       => 'First Name',
        'last_name'        => 'Last Name',
        'email'            => 'Email',
        'password'         => 'Password',
        'confirm_password' => 'Confirm Password',
        'gender'           => 'Gender',
        'role'             => 'Role',
        'address'          => 'Address'
    ];

    foreach ($required_fields as $field => $label) {
        if (empty($data[$field])) {
            $errors[] = "$label is required.";
        }
    }

    // Email format validation
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Password length validation (at least 8 characters)
    if (!empty($data['password']) && strlen($data['password']) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    // Password match validation
    if (!empty($data['password']) && !empty($data['confirm_password'])) {
        if ($data['password'] !== $data['confirm_password']) {
            $errors[] = "Password and Confirm Password do not match.";
        }
    }

    // Valid gender check
    $valid_genders = ['Male', 'Female', 'Other'];
    if (!empty($data['gender']) && !in_array($data['gender'], $valid_genders)) {
        $errors[] = "Invalid gender selection.";
    }

    // Valid role check
    $valid_roles = ['admin', 'user'];
    if (!empty($data['role']) && !in_array($data['role'], $valid_roles)) {
        $errors[] = "Invalid role selection.";
    }

    return $errors;
}

// ----------------------------
// USER FUNCTIONS
// ----------------------------

// Register a new user
function registerUser($data) {
    $pdo = getDBConnection();

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already exists.'];
    }

    // Hash the password
    $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        "INSERT INTO users (first_name, last_name, email, password, gender, role, address, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'active')"
    );

    if ($stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $hashed_password,
        $data['gender'],
        $data['role'],
        $data['address']
    ])) {
        return ['success' => true, 'message' => 'Registration successful!'];
    } else {
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

// Get user by email
function getUserByEmail($email) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(); // Returns associative array or false
}

// Get user by ID
function getUserById($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(); // Returns associative array or false
}

// Increment login attempts; lock if reaches 3
function incrementLoginAttempts($email) {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE email = ?");
    $stmt->execute([$email]);

    // Check if attempts reached 3 → set inactive
    $stmt2 = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE email = ? AND login_attempts >= 3");
    $stmt2->execute([$email]);
}

// Reset login attempts on successful login
function resetLoginAttempts($email) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE users SET login_attempts = 0 WHERE email = ?");
    $stmt->execute([$email]);
}

// Get all users (admin)
function getAllUsers($search = '') {
    $pdo = getDBConnection();

    if (!empty($search)) {
        // Using LIKE query for search
        $search_term = "%" . $search . "%";
        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? ORDER BY created_at DESC"
        );
        $stmt->execute([$search_term, $search_term, $search_term]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
        $stmt->execute();
    }

    // fetchAll returns all rows as an array — equivalent to the while loop
    return $stmt->fetchAll();
}

// Update user (admin)
function updateUser($id, $data) {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare(
        "UPDATE users SET first_name=?, last_name=?, email=?, gender=?, role=?, status=?, address=? WHERE id=?"
    );

    return $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $data['gender'],
        $data['role'],
        $data['status'],
        $data['address'],
        $id
    ]);
}

// Delete user
function deleteUser($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$id]);
}

// Toggle user status
function toggleUserStatus($id, $status) {
    $pdo = getDBConnection();
    $new_status = ($status === 'active') ? 'inactive' : 'active';
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $success = $stmt->execute([$new_status, $id]);
    return ['success' => $success, 'new_status' => $new_status];
}

// Update own profile
function updateProfile($id, $data) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare(
        "UPDATE users SET first_name=?, last_name=?, email=?, gender=?, address=? WHERE id=?"
    );
    return $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $data['gender'],
        $data['address'],
        $id
    ]);
}

// Change password
function changePassword($id, $current_password, $new_password) {
    $user = getUserById($id);
    if (!$user) {
        return ['success' => false, 'message' => 'User not found.'];
    }
    if (!password_verify($current_password, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect.'];
    }
    if (strlen($new_password) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters.'];
    }

    $hashed = password_hash($new_password, PASSWORD_BCRYPT);
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $success = $stmt->execute([$hashed, $id]);

    return $success
        ? ['success' => true,  'message' => 'Password changed successfully.']
        : ['success' => false, 'message' => 'Failed to change password.'];
}

// Check if session is valid; redirect if not
function requireLogin($redirect = 'login.php') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header("Location: $redirect");
        exit();
    }
}

// Check if user is admin; redirect if not
function requireAdmin($redirect = 'user_dashboard.php') {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: $redirect");
        exit();
    }
}
?>
