<?php
/**
 * User Login Page
 * Digital Marketing Campaign Tracker
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set flag so auth_check is skipped for the login page
$skip_auth = true;
require_once __DIR__ . '/includes/db_connect.php';

// If already logged in, redirect straight to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$email_val = '';

// Process Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $email_val = $email;

    // Validation
    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Prepared statement to look up user safely
            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            // Verify hashed password
            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);

                // Store user information in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                set_flash_message('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');
                header('Location: index.php');
                exit();
            } else {
                $error = 'Invalid email address or password. Please try again.';
            }
        } catch (PDOException $e) {
            $error = 'A database error occurred. Please try again later.';
        }
    }
}

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Digital Marketing Campaign Tracker</title>
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

    <div class="auth-card">
        <!-- Card Header with Brand -->
        <div class="auth-header">
            <div class="d-inline-flex p-3 rounded-circle bg-white text-primary mb-3 shadow-sm">
                <i class="bi bi-graph-up-arrow fs-2"></i>
            </div>
            <h3 class="fw-bold mb-1">AdTracker Pro</h3>
            <p class="mb-0 text-white-50 small">Digital Marketing Campaign Tracker</p>
        </div>

        <!-- Card Body -->
        <div class="auth-body">
            <h5 class="fw-bold text-dark mb-3">Sign In to Your Account</h5>

            <!-- Flash Alert -->
            <?php if ($flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type'] === 'error' ? 'danger' : $flash['type']) ?> alert-dismissible fade show small" role="alert">
                    <?= htmlspecialchars($flash['text']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Error Alert -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="login.php" method="POST" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email_val) ?>" placeholder="e.g. admin@tracker.com" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label small fw-semibold mb-0">Password</label>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary py-2 fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </button>
                </div>
            </form>

            <!-- Quick Demo Credentials Box -->
            <div class="p-3 bg-light rounded-3 border mb-3 small">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <strong class="text-secondary"><i class="bi bi-info-circle me-1"></i> Demo Credentials:</strong>
                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" onclick="fillDemoCredentials()">Auto-fill</button>
                </div>
                <div class="text-muted">
                    Email: <code>admin@tracker.com</code><br>
                    Password: <code>password123</code>
                </div>
            </div>

            <!-- Register Link -->
            <div class="text-center small text-muted">
                Don't have an account yet? 
                <a href="register.php" class="text-primary fw-semibold text-decoration-none">Create Account</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function fillDemoCredentials() {
            document.getElementById('email').value = 'admin@tracker.com';
            document.getElementById('password').value = 'password123';
        }
    </script>
</body>
</html>
