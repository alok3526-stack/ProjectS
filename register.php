<?php
$pageTitle = 'Register Account | CampusHire';
$activeNav = 'register';

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect_to_dashboard($_SESSION['user_role'] ?? 'student');
}

$errors = [];
$name = '';
$email = '';
$role = 'student';
$departmentOrIndustry = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = strtolower(trim($_POST['role'] ?? 'student'));
    $departmentOrIndustry = trim($_POST['department_or_industry'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name)) {
        $errors[] = 'Full Name or Company Name is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (!in_array($role, ['student', 'company'])) {
        $errors[] = 'Please select a valid account role (Student or Company).';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // Check if email already registered
    if (empty($errors)) {
        try {
            $existingUser = $usersCollection->findOne(['email' => $email]);
            if ($existingUser) {
                $errors[] = 'An account with this email address already exists.';
            }
        } catch (\Throwable $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

    // Insert user into MongoDB
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $userDoc = [
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => $role,
                'status' => 'approved', // auto-approved or can be verified by admin
                'created_at' => date('Y-m-d H:i:s')
            ];

            if ($role === 'company') {
                $userDoc['industry'] = $departmentOrIndustry ?: 'Technology';
            }

            $insertResult = $usersCollection->insertOne($userDoc);
            $newUserId = (string)$insertResult->getInsertedId();
            $userDoc['_id'] = $newUserId;

            // If student, create default profile record
            if ($role === 'student') {
                $profilesCollection->insertOne([
                    'user_id' => $newUserId,
                    'name' => $name,
                    'email' => $email,
                    'cgpa' => 0.0,
                    'skills' => '',
                    'department' => $departmentOrIndustry ?: 'Engineering',
                    'phone' => '',
                    'graduation_year' => date('Y') + 1,
                    'resume_link' => '',
                    'bio' => '',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }

            // Automatically log in the user
            login_user($userDoc);

        } catch (\Throwable $e) {
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card card-custom p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="stat-icon primary mx-auto mb-3" style="width: 60px; height: 60px; border-radius: 50%;">
                        <i class="bi bi-person-plus-fill fs-3"></i>
                    </div>
                    <h2 class="h3 fw-bold mb-1">Create an Account</h2>
                    <p class="text-muted small">Join CampusHire to unlock placement drives and recruit top talent</p>
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

                <form method="POST" action="register.php" novalidate>
                    <!-- Role Selection -->
                    <div class="mb-4">
                        <label class="form-label d-block text-center mb-2">Select Your Role</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="card p-3 text-center border cursor-pointer h-100 role-selector <?= $role === 'student' ? 'border-primary bg-light' : '' ?>" style="cursor: pointer;">
                                    <input type="radio" name="role" value="student" class="d-none" <?= $role === 'student' ? 'checked' : '' ?> onchange="updateRoleUI('student')">
                                    <i class="bi bi-mortarboard fs-2 text-primary mb-1"></i>
                                    <div class="fw-bold">Student</div>
                                    <small class="text-muted" style="font-size: 0.75rem;">Apply for jobs</small>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="card p-3 text-center border cursor-pointer h-100 role-selector <?= $role === 'company' ? 'border-primary bg-light' : '' ?>" style="cursor: pointer;">
                                    <input type="radio" name="role" value="company" class="d-none" <?= $role === 'company' ? 'checked' : '' ?> onchange="updateRoleUI('company')">
                                    <i class="bi bi-building fs-2 text-primary mb-1"></i>
                                    <div class="fw-bold">Company</div>
                                    <small class="text-muted" style="font-size: 0.75rem;">Post job drives</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label" id="name-label">
                            <?= $role === 'company' ? 'Company Name' : 'Full Name' ?>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                            <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($name) ?>" placeholder="e.g. Jane Doe" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($email) ?>" placeholder="e.g. user@campus.edu" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="dept-industry" class="form-label" id="dept-label">
                            <?= $role === 'company' ? 'Industry / Domain' : 'Department / Major' ?>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-tag"></i></span>
                            <input type="text" name="department_or_industry" id="dept-industry" class="form-control" value="<?= htmlspecialchars($departmentOrIndustry) ?>" placeholder="e.g. Computer Science / Software">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-shield-lock"></i></span>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="••••••••" required>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary-custom py-2">
                            <i class="bi bi-person-check-fill me-1"></i> Create Account
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="text-muted small mb-0">
                        Already have an account? <a href="/login.php" class="text-primary fw-bold text-decoration-none">Log in here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateRoleUI(selectedRole) {
    document.querySelectorAll('.role-selector').forEach(el => {
        el.classList.remove('border-primary', 'bg-light');
    });
    event.currentTarget.classList.add('border-primary', 'bg-light');

    const nameLabel = document.getElementById('name-label');
    const deptLabel = document.getElementById('dept-label');
    const nameInput = document.getElementById('name');
    const deptInput = document.getElementById('dept-industry');

    if (selectedRole === 'company') {
        nameLabel.innerText = 'Company Name';
        deptLabel.innerText = 'Industry / Domain';
        nameInput.placeholder = 'e.g. Acme Innovations Corp';
        deptInput.placeholder = 'e.g. Cloud Computing & AI';
    } else {
        nameLabel.innerText = 'Full Name';
        deptLabel.innerText = 'Department / Major';
        nameInput.placeholder = 'e.g. Alex Johnson';
        deptInput.placeholder = 'e.g. Computer Science';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
