<?php
$pageTitle = 'Recruitment Portal | CampusHire';
$activeNav = 'company';

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce company role
require_role('company');

$currentUser = get_auth_user();
$companyId = $currentUser['id'];
$companyName = $currentUser['name'];

// 1. Handle New Job Drive Creation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_job') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $minCgpa = (float)($_POST['min_cgpa'] ?? 0.0);
    $ctc = trim($_POST['ctc'] ?? '');
    $location = trim($_POST['location'] ?? 'Remote');
    $deadline = trim($_POST['deadline'] ?? date('Y-m-d', strtotime('+30 days')));

    if (empty($title)) {
        set_flash_message('danger', 'Job Title is required.');
    } elseif ($minCgpa < 0 || $minCgpa > 10.0) {
        set_flash_message('danger', 'Minimum CGPA required must be between 0.0 and 10.0.');
    } else {
        try {
            $jobsCollection->insertOne([
                'company_id' => $companyId,
                'company_name' => $companyName,
                'title' => $title,
                'description' => $description,
                'min_cgpa' => $minCgpa,
                'ctc' => $ctc ?: 'Competitive',
                'location' => $location,
                'deadline' => $deadline,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            set_flash_message('success', 'Job Drive for "' . htmlspecialchars($title) . '" has been published successfully!');
            header('Location: company.php');
            exit;
        } catch (\Throwable $e) {
            set_flash_message('danger', 'Error publishing job drive: ' . $e->getMessage());
        }
    }
}

// 2. Handle Application Status Update POST (Applied -> Shortlisted -> Hired -> Rejected)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_applicant_status') {
    $appId = trim($_POST['application_id'] ?? '');
    $newStatus = trim($_POST['status'] ?? 'Applied');
    $notes = trim($_POST['notes'] ?? '');

    $validStatuses = ['Applied', 'Shortlisted', 'Hired', 'Rejected'];
    if (!in_array($newStatus, $validStatuses)) {
        set_flash_message('danger', 'Invalid applicant status provided.');
    } else {
        try {
            $applicationsCollection->updateOne(
                ['_id' => $appId],
                ['$set' => [
                    'status' => $newStatus,
                    'notes' => $notes ?: 'Status updated to ' . $newStatus,
                    'updated_at' => date('Y-m-d H:i:s')
                ]]
            );

            set_flash_message('success', 'Candidate status successfully updated to ' . htmlspecialchars($newStatus) . '!');
            header('Location: company.php#applicants-section');
            exit;
        } catch (\Throwable $e) {
            set_flash_message('danger', 'Error updating candidate status: ' . $e->getMessage());
        }
    }
}

// 3. Handle Job Status Toggle (Close / Activate) or Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_job') {
    $jobId = trim($_POST['job_id'] ?? '');
    $currentStatus = trim($_POST['current_status'] ?? 'active');
    $newStatus = ($currentStatus === 'active') ? 'closed' : 'active';

    try {
        $jobsCollection->updateOne(
            ['_id' => $jobId, 'company_id' => $companyId],
            ['$set' => ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')]]
        );
        set_flash_message('info', 'Job drive status changed to ' . ucfirst($newStatus) . '.');
        header('Location: company.php');
        exit;
    } catch (\Throwable $e) {
        set_flash_message('danger', 'Failed to toggle job: ' . $e->getMessage());
    }
}

// 4. Fetch Company's Job Drives
$companyJobs = [];
try {
    $companyJobs = $jobsCollection->find(['company_id' => $companyId], ['sort' => ['created_at' => -1]]);
} catch (\Throwable $e) {}

// 5. Fetch Applications Received for This Company
$companyApplications = [];
try {
    $companyApplications = $applicationsCollection->find(['company_id' => $companyId], ['sort' => ['applied_at' => -1]]);
} catch (\Throwable $e) {}

// Calculate ATS metrics
$totalJobs = count($companyJobs);
$totalApplicants = count($companyApplications);
$shortlistedCount = 0;
$hiredCount = 0;

foreach ($companyApplications as $app) {
    $s = strtolower($app['status'] ?? '');
    if ($s === 'shortlisted') $shortlistedCount++;
    if ($s === 'hired') $hiredCount++;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header Banner -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">
                <i class="bi bi-building text-primary me-2"></i><?= htmlspecialchars($companyName) ?>
            </h1>
            <p class="text-muted small mb-0">Campus Recruitment & Applicant Tracking System (ATS)</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#newJobModal">
                <i class="bi bi-plus-circle me-1"></i> Post New Job Drive
            </button>
        </div>
    </div>

    <!-- Metric Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="bi bi-briefcase-fill"></i>
                </div>
                <div>
                    <div class="stat-value text-primary"><?= (int)$totalJobs ?></div>
                    <div class="stat-label">Total Job Drives</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon secondary">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div class="stat-value text-secondary"><?= (int)$totalApplicants ?></div>
                    <div class="stat-label">Applications Received</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div>
                    <div class="stat-value text-warning"><?= (int)$shortlistedCount ?></div>
                    <div class="stat-label">Shortlisted Candidates</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="bi bi-award-fill"></i>
                </div>
                <div>
                    <div class="stat-value text-success"><?= (int)$hiredCount ?></div>
                    <div class="stat-label">Hired / Placed</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 gap-2" id="companyTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active px-4 py-2 fw-semibold" id="applicants-tab" data-bs-toggle="tab" data-bs-target="#applicants-panel" type="button" role="tab">
                <i class="bi bi-person-check-fill me-1"></i> Applicant Tracking Pipeline (<?= $totalApplicants ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-4 py-2 fw-semibold" id="drives-tab" data-bs-toggle="tab" data-bs-target="#drives-panel" type="button" role="tab">
                <i class="bi bi-folder2-open me-1"></i> My Job Drives (<?= $totalJobs ?>)
            </button>
        </li>
    </ul>

    <!-- Tab Panels -->
    <div class="tab-content" id="companyTabContent">

        <!-- TAB 1: APPLICANT TRACKING SYSTEM (ATS) -->
        <div class="tab-pane fade show active" id="applicants-panel" role="tabpanel">
            <div class="card card-custom overflow-hidden">
                <div class="card-custom-header d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">Candidate Review & Status Pipeline</h5>
                        <p class="text-muted small mb-0">Move students through the recruitment lifecycle: Applied &rarr; Shortlisted &rarr; Hired</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-funnel me-1"></i><?= $totalApplicants ?> Total Applicants
                        </span>
                    </div>
                </div>

                <?php if (empty($companyApplications)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-people fs-1 mb-2"></i>
                        <h5>No Student Applications Received Yet</h5>
                        <p class="small mb-3">Ensure your job drives are active and students meet the specified CGPA cutoffs.</p>
                        <button type="button" class="btn btn-primary-custom btn-sm" data-bs-toggle="modal" data-bs-target="#newJobModal">
                            <i class="bi bi-plus-circle me-1"></i> Post a New Job Drive
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Candidate Details</th>
                                    <th>Academic CGPA</th>
                                    <th>Target Position</th>
                                    <th>Portfolio / Resume</th>
                                    <th>Current Pipeline Status</th>
                                    <th>Status Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($companyApplications as $app): ?>
                                    <?php 
                                        $appIdStr = (string)$app['_id'];
                                        $currentAppStatus = $app['status'] ?? 'Applied';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="stat-icon primary" style="width: 38px; height: 38px; font-size: 1rem;">
                                                    <i class="bi bi-person-fill"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($app['student_name'] ?? 'Candidate') ?></div>
                                                    <small class="text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($app['student_email'] ?? '') ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
                                                <?= number_format((float)($app['student_cgpa'] ?? 0), 2) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($app['job_title'] ?? 'Role') ?></div>
                                            <small class="text-muted"><i class="bi bi-clock me-1"></i><?= htmlspecialchars(date('M d, Y', strtotime($app['applied_at'] ?? 'now'))) ?></small>
                                        </td>
                                        <td>
                                            <?php if (!empty($app['resume_link'])): ?>
                                                <a href="<?= htmlspecialchars($app['resume_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-file-earmark-pdf me-1"></i>View Resume
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">Not provided</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge-status <?= strtolower($currentAppStatus) ?>">
                                                <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($currentAppStatus) ?>
                                            </span>
                                            <?php if (!empty($app['notes'])): ?>
                                                <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                                                    <?= htmlspecialchars($app['notes']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- Inline Status Updater Form -->
                                            <form method="POST" action="company.php" class="d-flex align-items-center gap-1">
                                                <input type="hidden" name="action" value="update_applicant_status">
                                                <input type="hidden" name="application_id" value="<?= htmlspecialchars($appIdStr) ?>">
                                                
                                                <select name="status" class="form-select form-select-sm" style="width: 130px; font-size: 0.82rem;">
                                                    <option value="Applied" <?= $currentAppStatus === 'Applied' ? 'selected' : '' ?>>Applied</option>
                                                    <option value="Shortlisted" <?= $currentAppStatus === 'Shortlisted' ? 'selected' : '' ?>>Shortlisted</option>
                                                    <option value="Hired" <?= $currentAppStatus === 'Hired' ? 'selected' : '' ?>>Hired</option>
                                                    <option value="Rejected" <?= $currentAppStatus === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Save Status Change">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB 2: JOB DRIVES MANAGEMENT -->
        <div class="tab-pane fade" id="drives-panel" role="tabpanel">
            <div class="card card-custom overflow-hidden">
                <div class="card-custom-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">Active Campus Hiring Drives</h5>
                        <p class="text-muted small mb-0">Manage requirements, CGPA criteria, and drive status</p>
                    </div>
                    <button type="button" class="btn btn-primary-custom btn-sm" data-bs-toggle="modal" data-bs-target="#newJobModal">
                        <i class="bi bi-plus-lg me-1"></i> Create New Drive
                    </button>
                </div>

                <?php if (empty($companyJobs)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-briefcase fs-1 mb-2"></i>
                        <h5>No Drives Created Yet</h5>
                        <p class="small mb-3">Click the button below to publish your first campus recruitment drive.</p>
                        <button type="button" class="btn btn-primary-custom btn-sm" data-bs-toggle="modal" data-bs-target="#newJobModal">
                            Post New Job Drive
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Role Title</th>
                                    <th>Min CGPA Cutoff</th>
                                    <th>Compensation / CTC</th>
                                    <th>Location</th>
                                    <th>Application Deadline</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($companyJobs as $job): ?>
                                    <?php 
                                        $jobIdStr = (string)$job['_id'];
                                        $isActive = ($job['status'] ?? 'active') === 'active';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($job['title']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars(substr($job['description'] ?? '', 0, 60)) ?>...</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle fw-bold">
                                                &ge; <?= number_format((float)($job['min_cgpa'] ?? 0), 1) ?> CGPA
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($job['ctc'] ?? 'Competitive') ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($job['location'] ?? 'Remote') ?></small>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($job['deadline'] ?? 'N/A') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge-status <?= $isActive ? 'active' : 'closed' ?>">
                                                <?= $isActive ? 'Active' : 'Closed' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" action="company.php" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_job">
                                                <input type="hidden" name="job_id" value="<?= htmlspecialchars($jobIdStr) ?>">
                                                <input type="hidden" name="current_status" value="<?= $isActive ? 'active' : 'closed' ?>">
                                                <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= $isActive ? 'Close Drive' : 'Reactivate Drive' ?>">
                                                    <i class="bi <?= $isActive ? 'bi-pause-circle' : 'bi-play-circle' ?> me-1"></i>
                                                    <?= $isActive ? 'Close' : 'Activate' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Modal: Post New Job Drive -->
<div class="modal fade" id="newJobModal" tabindex="-1" aria-labelledby="newJobModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card-custom border-0 shadow-lg">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="stat-icon primary" style="width: 36px; height: 36px; font-size: 1.1rem;">
                        <i class="bi bi-briefcase-fill"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold" id="newJobModalLabel">Post New Campus Job Drive</h5>
                        <small class="text-muted">Broadcast to all eligible campus students</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="company.php">
                <input type="hidden" name="action" value="create_job">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="job-title" class="form-label">Job Title / Designation *</label>
                        <input type="text" name="title" id="job-title" class="form-control" placeholder="e.g. Associate Software Development Engineer" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="job-min-cgpa" class="form-label text-primary fw-bold">
                                <i class="bi bi-mortarboard-fill me-1"></i> Minimum CGPA Cutoff (0.0 - 10.0) *
                            </label>
                            <input type="number" step="0.1" min="0" max="10.0" name="min_cgpa" id="job-min-cgpa" class="form-control fw-bold text-primary" placeholder="e.g. 7.5" required>
                            <small class="form-text text-muted">Students below this CGPA will NOT see this job drive on their board.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="job-ctc" class="form-label">Compensation Package (CTC)</label>
                            <input type="text" name="ctc" id="job-ctc" class="form-control" placeholder="e.g. $105,000 / 18 LPA">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="job-location" class="form-label">Work Location</label>
                            <input type="text" name="location" id="job-location" class="form-control" placeholder="e.g. New York, NY / Remote / Hybrid">
                        </div>
                        <div class="col-md-6">
                            <label for="job-deadline" class="form-label">Application Deadline</label>
                            <input type="date" name="deadline" id="job-deadline" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="job-desc" class="form-label">Job Description & Requirements *</label>
                        <textarea name="description" id="job-desc" rows="4" class="form-control" placeholder="Detail the role responsibilities, ideal skills (e.g. PHP, Python, SQL), and interview process..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom px-4">
                        <i class="bi bi-send-check-fill me-1"></i> Publish Job Drive
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
