<?php
$pageTitle = 'Admin Portal | CampusHire';
$activeNav = 'admin';

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce admin role
require_role('admin');

$currentUser = get_auth_user();

// 1. Handle Account Verification / Status Toggle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_user_status') {
    $targetUserId = trim($_POST['user_id'] ?? '');
    $newStatus = trim($_POST['new_status'] ?? 'approved');

    if (in_array($newStatus, ['approved', 'pending', 'rejected'])) {
        try {
            $usersCollection->updateOne(
                ['_id' => $targetUserId],
                ['$set' => ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')]]
            );
            set_flash_message('success', 'User verification status updated to "' . ucfirst($newStatus) . '".');
            header('Location: admin.php');
            exit;
        } catch (\Throwable $e) {
            set_flash_message('danger', 'Error updating user status: ' . $e->getMessage());
        }
    }
}

// 2. Handle Delete User POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $targetUserId = trim($_POST['user_id'] ?? '');
    
    // Prevent admin from deleting themselves
    if ($targetUserId === $currentUser['id']) {
        set_flash_message('danger', 'You cannot delete your own admin account.');
    } else {
        try {
            $usersCollection->deleteOne(['_id' => $targetUserId]);
            $profilesCollection->deleteOne(['user_id' => $targetUserId]);
            set_flash_message('info', 'User account and associated records deleted.');
            header('Location: admin.php');
            exit;
        } catch (\Throwable $e) {
            set_flash_message('danger', 'Error deleting user: ' . $e->getMessage());
        }
    }
}

// 3. Fetch All Students and Companies
$students = [];
$companies = [];
$allJobs = [];
$allApplications = [];

try {
    $students = $usersCollection->find(['role' => 'student'], ['sort' => ['created_at' => -1]]);
    $companies = $usersCollection->find(['role' => 'company'], ['sort' => ['created_at' => -1]]);
    $allJobs = $jobsCollection->find([], ['sort' => ['created_at' => -1]]);
    $allApplications = $applicationsCollection->find([], ['sort' => ['applied_at' => -1]]);
} catch (\Throwable $e) {}

// Build lookup map for student CGPAs from profilesCollection
$studentProfiles = [];
try {
    $profiles = $profilesCollection->find([]);
    foreach ($profiles as $p) {
        $studentProfiles[(string)$p['user_id']] = $p;
    }
} catch (\Throwable $e) {}

// Calculate counts
$pendingStudents = 0;
foreach ($students as $s) {
    if (($s['status'] ?? 'pending') === 'pending') $pendingStudents++;
}

$pendingCompanies = 0;
foreach ($companies as $c) {
    if (($c['status'] ?? 'pending') === 'pending') $pendingCompanies++;
}

$totalPending = $pendingStudents + $pendingCompanies;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">
                <i class="bi bi-shield-lock-fill text-primary me-2"></i>Campus Placement Administration
            </h1>
            <p class="text-muted small mb-0">Governance, Institutional Verification & Audit Dashboard</p>
        </div>
        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
            <span class="badge bg-dark px-3 py-2">
                <i class="bi bi-person-check me-1"></i> <?= count($students) ?> Students &bull; <?= count($companies) ?> Companies
            </span>
        </div>
    </div>

    <!-- Pending Verification Notice if any -->
    <?php if ($totalPending > 0): ?>
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
                <div>
                    <strong class="d-block">Pending Account Verifications (<?= $totalPending ?>)</strong>
                    <span class="small"><?= $pendingStudents ?> student(s) and <?= $pendingCompanies ?> company account(s) are awaiting admin verification.</span>
                </div>
            </div>
            <a href="#students-section" class="btn btn-sm btn-dark">Review Now &darr;</a>
        </div>
    <?php endif; ?>

    <!-- Stat Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div>
                    <div class="stat-value text-primary"><?= count($students) ?></div>
                    <div class="stat-label">Registered Students</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="bi bi-buildings-fill"></i>
                </div>
                <div>
                    <div class="stat-value text-success"><?= count($companies) ?></div>
                    <div class="stat-label">Partner Companies</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon secondary">
                    <i class="bi bi-briefcase-fill"></i>
                </div>
                <div>
                    <div class="stat-value text-secondary"><?= count($allJobs) ?></div>
                    <div class="stat-label">Campus Job Drives</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                </div>
                <div>
                    <div class="stat-value text-warning"><?= count($allApplications) ?></div>
                    <div class="stat-label">Total Applications</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Nav Tabs -->
    <ul class="nav nav-pills mb-4 gap-2" id="adminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active px-4 py-2 fw-semibold" id="students-tab" data-bs-toggle="tab" data-bs-target="#students-panel" type="button" role="tab">
                <i class="bi bi-people-fill me-1"></i> Students Management (<?= count($students) ?>)
                <?php if ($pendingStudents > 0): ?>
                    <span class="badge bg-warning text-dark ms-1"><?= $pendingStudents ?> pending</span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-4 py-2 fw-semibold" id="companies-tab" data-bs-toggle="tab" data-bs-target="#companies-panel" type="button" role="tab">
                <i class="bi bi-building-check me-1"></i> Companies Management (<?= count($companies) ?>)
                <?php if ($pendingCompanies > 0): ?>
                    <span class="badge bg-warning text-dark ms-1"><?= $pendingCompanies ?> pending</span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-4 py-2 fw-semibold" id="jobs-tab" data-bs-toggle="tab" data-bs-target="#jobs-panel" type="button" role="tab">
                <i class="bi bi-list-stars me-1"></i> Drives & Placement Audit (<?= count($allJobs) ?>)
            </button>
        </li>
    </ul>

    <!-- Tab Panels -->
    <div class="tab-content" id="adminTabContent">

        <!-- TAB 1: STUDENTS MANAGEMENT -->
        <div class="tab-pane fade show active" id="students-panel" role="tabpanel">
            <div class="card card-custom overflow-hidden" id="students-section">
                <div class="card-custom-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">Registered Student Directory</h5>
                        <p class="text-muted small mb-0">Verify credentials, inspect academic CGPA, and manage student accounts</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Department & Year</th>
                                <th>Academic CGPA</th>
                                <th>Account Status</th>
                                <th>Verification Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <?php 
                                    $sId = (string)$student['_id'];
                                    $prof = $studentProfiles[$sId] ?? null;
                                    $status = $student['status'] ?? 'pending';
                                    $isApproved = ($status === 'approved');
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="stat-icon primary" style="width: 36px; height: 36px; font-size: 0.95rem;">
                                                <i class="bi bi-mortarboard"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($student['name']) ?></div>
                                                <small class="text-muted">Registered <?= htmlspecialchars(date('M d, Y', strtotime($student['created_at'] ?? 'now'))) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($student['email']) ?></code>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark"><?= htmlspecialchars($prof['department'] ?? 'Not specified') ?></div>
                                        <small class="text-muted">Grad: <?= htmlspecialchars($prof['graduation_year'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border fs-6">
                                            <?= number_format((float)($prof['cgpa'] ?? 0.0), 2) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-status <?= $isApproved ? 'approved' : 'pending' ?>">
                                            <i class="bi <?= $isApproved ? 'bi-patch-check-fill' : 'bi-hourglass-split' ?> me-1"></i>
                                            <?= ucfirst(htmlspecialchars($status)) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 align-items-center">
                                            <form method="POST" action="admin.php" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_user_status">
                                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($sId) ?>">
                                                <input type="hidden" name="new_status" value="<?= $isApproved ? 'pending' : 'approved' ?>">
                                                <button type="submit" class="btn btn-sm <?= $isApproved ? 'btn-outline-warning' : 'btn-success' ?>">
                                                    <i class="bi <?= $isApproved ? 'bi-shield-slash' : 'bi-shield-check' ?> me-1"></i>
                                                    <?= $isApproved ? 'Revoke Approval' : 'Approve & Verify' ?>
                                                </button>
                                            </form>

                                            <form method="POST" action="admin.php" class="d-inline" onsubmit="return confirm('Delete student <?= addslashes($student['name']) ?> permanently?')">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($sId) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Account">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 2: COMPANIES MANAGEMENT -->
        <div class="tab-pane fade" id="companies-panel" role="tabpanel">
            <div class="card card-custom overflow-hidden">
                <div class="card-custom-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">Registered Partner Companies</h5>
                        <p class="text-muted small mb-0">Verify corporate recruiters before allowing campus drive listings</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Contact Email</th>
                                <th>Industry Domain</th>
                                <th>Registration Status</th>
                                <th>Verification Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($companies as $comp): ?>
                                <?php 
                                    $cId = (string)$comp['_id'];
                                    $status = $comp['status'] ?? 'pending';
                                    $isApproved = ($status === 'approved');
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="stat-icon success" style="width: 36px; height: 36px; font-size: 0.95rem;">
                                                <i class="bi bi-building"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($comp['name']) ?></div>
                                                <small class="text-muted">Registered <?= htmlspecialchars(date('M d, Y', strtotime($comp['created_at'] ?? 'now'))) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($comp['email']) ?></code>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($comp['industry'] ?? 'Technology') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-status <?= $isApproved ? 'approved' : 'pending' ?>">
                                            <i class="bi <?= $isApproved ? 'bi-patch-check-fill' : 'bi-hourglass-split' ?> me-1"></i>
                                            <?= ucfirst(htmlspecialchars($status)) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 align-items-center">
                                            <form method="POST" action="admin.php" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_user_status">
                                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($cId) ?>">
                                                <input type="hidden" name="new_status" value="<?= $isApproved ? 'pending' : 'approved' ?>">
                                                <button type="submit" class="btn btn-sm <?= $isApproved ? 'btn-outline-warning' : 'btn-success' ?>">
                                                    <i class="bi <?= $isApproved ? 'bi-shield-slash' : 'bi-shield-check' ?> me-1"></i>
                                                    <?= $isApproved ? 'Revoke Approval' : 'Approve & Verify' ?>
                                                </button>
                                            </form>

                                            <form method="POST" action="admin.php" class="d-inline" onsubmit="return confirm('Delete company <?= addslashes($comp['name']) ?> permanently?')">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($cId) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Account">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 3: DRIVES & PLACEMENT AUDIT -->
        <div class="tab-pane fade" id="jobs-panel" role="tabpanel">
            <div class="card card-custom overflow-hidden">
                <div class="card-custom-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">All Campus Hiring Drives</h5>
                        <p class="text-muted small mb-0">Audit job cutoffs, packages, and application volumes across campus</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Role Title & Company</th>
                                <th>Min CGPA Required</th>
                                <th>CTC Package</th>
                                <th>Drive Deadline</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allJobs as $job): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($job['title']) ?></div>
                                        <small class="text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($job['company_name'] ?? 'Company') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold">
                                            &ge; <?= number_format((float)($job['min_cgpa'] ?? 0), 1) ?> CGPA
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($job['ctc'] ?? 'Competitive') ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars($job['deadline'] ?? 'Open') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-status <?= ($job['status'] ?? 'active') === 'active' ? 'active' : 'closed' ?>">
                                            <?= ucfirst(htmlspecialchars($job['status'] ?? 'active')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars(date('M d, Y', strtotime($job['created_at'] ?? 'now'))) ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
