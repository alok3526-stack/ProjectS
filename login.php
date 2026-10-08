<?php
$pageTitle = 'Login | CampusHire';
$activeNav = 'login';

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect_to_dashboard($_SESSION['user_role'] ?? 'student');
}

$errors = [];
$email = '';

// Handle logout query
if (isset($_GET['logout'])) {
    set_flash_message('info', 'You have been successfully logged out.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $errors[] = 'Please enter your email address.';
    }
    if (empty($password)) {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        try {
            $user = $usersCollection->findOne(['email' => $email]);

            if ($user && password_verify($password, $user['password'])) {
                // Successful verification
                login_user($user);
            } else {
                $errors[] = 'Invalid email address or password.';
            }
        } catch (\Throwable $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <!-- Demo Quick Login Helper Banner -->
            <div class="card card-custom mb-3 bg-light border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-bold text-muted text-uppercase">
                            <i class="bi bi-lightning-charge-fill text-warning me-1"></i>Quick Demo Fill
                        </span>
                        <span class="badge bg-secondary-subtle text-secondary small">1-Click Test</span>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="fillDemoCredentials('student')">
                            <i class="bi bi-mortarboard me-1"></i>Student
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="fillDemoCredentials('company')">
                            <i class="bi bi-building me-1"></i>Company
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-dark" onclick="fillDemoCredentials('admin')">
                            <i class="bi bi-shield-lock me-1"></i>Admin
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Login Card -->
            <div class="card card-custom p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="stat-icon primary mx-auto mb-3" style="width: 60px; height: 60px; border-radius: 50%;">
                        <i class="bi bi-box-arrow-in-right fs-3"></i>
                    </div>
                    <h2 class="h3 fw-bold mb-1">Welcome Back</h2>
                    <p class="text-muted small">Sign in to access your placement dashboard</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger shadow-sm">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" novalidate>
                    <div class="mb-3">
                        <label for="login-email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" id="login-email" class="form-control" value="<?= htmlspecialchars($email) ?>" placeholder="user@campus.edu" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="login-password" class="form-label mb-0">Password</label>
                            <span class="small text-muted">Secured via password_hash()</span>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="login-password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary-custom py-2">
                            <i class="bi bi-unlock-fill me-1"></i> Sign In to Dashboard
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="text-muted small mb-0">
                        Don't have an account yet? <a href="/register.php" class="text-primary fw-bold text-decoration-none">Register here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
