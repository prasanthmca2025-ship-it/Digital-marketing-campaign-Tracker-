<?php
/**
 * User Registration Page
 * Digital Marketing Campaign Tracker
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$skip_auth = true;
require_once __DIR__ . '/includes/db_connect.php';

// If already logged in, redirect straight to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$errors = [];
$name_val = '';
$email_val = '';
$role_val = 'Marketer';

// Process Registration Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'Marketer');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $name_val = $name;
    $email_val = $email;
    $role_val = $role;

    // Backend Validations
    if (empty($name)) {
        $errors[] = 'Please provide your full name.';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Full name must be at least 2 characters.';
    }

    if (empty($email)) {
        $errors[] = 'Please provide an email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($password)) {
        $errors[] = 'Please provide a password.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    // Check if email already exists in database
    if (empty($errors)) {
        try {
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $checkStmt->execute([':email' => $email]);
            if ($checkStmt->fetch()) {
                $errors[] = 'An account with this email address already exists.';
            } else {
                // Hash password securely with bcrypt
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert into users table
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, role, created_at)
                    VALUES (:name, :email, :password, :role, NOW())
                ");
                $insertStmt->execute([
                    ':name'     => $name,
                    ':email'    => $email,
                    ':password' => $hashed_password,
                    ':role'     => $role
                ]);

                set_flash_message('success', 'Registration successful! You can now log in.');
                header('Location: login.php');
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = 'A database error occurred during registration. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Digital Marketing Campaign Tracker</title>
    <!-- Google Font: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="auth-wrapper">

    <div class="auth-card" style="max-width: 480px;">
        <!-- Header -->
        <div class="auth-header">
            <div class="d-inline-flex p-3 rounded-circle bg-white text-primary mb-3 shadow-sm">
                <i class="bi bi-person-plus fs-2"></i>
            </div>
            <h3 class="fw-bold mb-1">Create Account</h3>
            <p class="mb-0 text-white-50 small">Start tracking campaigns with AdTracker Pro</p>
        </div>

        <!-- Body -->
        <div class="auth-body">
            <!-- Errors Alert -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                    <strong>Please fix the following issues:</strong>
                    <ul class="mb-0 ps-3 mt-1">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form action="register.php" method="POST" novalidate>
                <div class="mb-3">
                    <label for="name" class="form-label small fw-semibold">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($name_val) ?>" placeholder="e.g. Jane Doe" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email_val) ?>" placeholder="e.g. jane@company.com" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label small fw-semibold">Role / Title</label>
                    <select class="form-select" id="role" name="role">
                        <option value="Digital Marketer" <?= ($role_val === 'Digital Marketer' || $role_val === 'Marketer') ? 'selected' : '' ?>>Digital Marketer</option>
                        <option value="Media Buyer" <?= ($role_val === 'Media Buyer') ? 'selected' : '' ?>>Media Buyer</option>
                        <option value="SEO Specialist" <?= ($role_val === 'SEO Specialist') ? 'selected' : '' ?>>SEO Specialist</option>
                        <option value="PPC Specialist" <?= ($role_val === 'PPC Specialist') ? 'selected' : '' ?>>PPC Specialist</option>
                        <option value="Marketing Manager" <?= ($role_val === 'Marketing Manager') ? 'selected' : '' ?>>Marketing Manager</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-sm-6 mb-3">
                        <label for="password" class="form-label small fw-semibold">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Min. 6 chars" required>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label for="confirm_password" class="form-label small fw-semibold">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                    </div>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary py-2 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Register Account
                    </button>
                </div>
            </form>

            <!-- Login Link -->
            <div class="text-center small text-muted">
                Already have an account? 
                <a href="login.php" class="text-primary fw-semibold text-decoration-none">Sign In</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
