<?php
$pageTitle = 'Student Portal | CampusHire';
$activeNav = 'student';

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce student role
require_role('student');

$currentUser = get_auth_user();
$studentId = $currentUser['id'];

// 1. Fetch or create student profile
$profile = $profilesCollection->findOne(['user_id' => $studentId]);
if (!$profile) {
    $initialProfile = [
        'user_id' => $studentId,
        'name' => $currentUser['name'],
        'email' => $currentUser['email'],
        'cgpa' => 0.0,
        'skills' => '',
        'department' => 'Computer Science',
        'phone' => '',
        'graduation_year' => date('Y') + 1,
        'resume_link' => '',
        'bio' => '',
        'updated_at' => date('Y-m-d H:i:s')
    ];
    $profilesCollection->insertOne($initialProfile);
    $profile = $initialProfile;
}

$currentCgpa = (float)($profile['cgpa'] ?? 0.0);

// 2. Handle Profile Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $cgpa = (float)($_POST['cgpa'] ?? 0.0);
    $skills = trim($_POST['skills'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $graduationYear = trim($_POST['graduation_year'] ?? '');
    $resumeLink = trim($_POST['resume_link'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $name = trim($_POST['name'] ?? $currentUser['name']);

    if ($cgpa < 0 || $cgpa > 10.0) {
        set_flash_message('danger', 'CGPA must be a value between 0.0 and 10.0.');
    } else {
        try {
            $profilesCollection->updateOne(
                ['user_id' => $studentId],
                ['$set' => [
                    'name' => $name,
                    'cgpa' => $cgpa,
                    'skills' => $skills,
                    'department' => $department,
                    'phone' => $phone,
                    'graduation_year' => $graduationYear,
                    'resume_link' => $resumeLink,
                    'bio' => $bio,
                    'updated_at' => date('Y-m-d H:i:s')
                ]]
            );

            // Update user name in session and users collection
            $usersCollection->updateOne(['_id' => $studentId], ['$set' => ['name' => $name]]);
            $_SESSION['user_name'] = $name;

            set_flash_message('success', 'Profile updated successfully! Your eligible job listings have been refreshed.');
            header('Location: student.php');
            exit;
        } catch (\Throwable $e) {
            set_flash_message('danger', 'Error updating profile: ' . $e->getMessage());
        }
    }
}

// 3. Handle 1-Click Job Apply POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_job') {
    $jobId = trim($_POST['job_id'] ?? '');

    try {
        $job = $jobsCollection->findOne(['_id' => $jobId]);
        if (!$job) {
            set_flash_message('danger', 'Selected job drive does not exist or has closed.');
        } else {
            $minCgpa = (float)($job['min_cgpa'] ?? 0.0);

            // Eligibility verification
            if ($currentCgpa < $minCgpa) {
                set_flash_message('danger', 'Eligibility check failed: Your CGPA (' . $currentCgpa . ') is below the required minimum (' . $minCgpa . ').');
            } else {
                // Check if already applied
                $existingApp = $applicationsCollection->findOne([
                    'job_id' => $jobId,
                    'student_id' => $studentId
                ]);

                if ($existingApp) {
                    set_flash_message('warning', 'You have already applied for this position.');
                } else {
                    $applicationsCollection->insertOne([
                        'job_id' => $jobId,
                        'student_id' => $studentId,
                        'student_name' => $profile['name'] ?? $currentUser['name'],
                        'student_email' => $currentUser['email'],
                        'student_cgpa' => $currentCgpa,
                        'job_title' => $job['title'] ?? 'Software Role',
                        'company_id' => $job['company_id'] ?? '',
                        'company_name' => $job['company_name'] ?? 'Company',
                        'resume_link' => $profile['resume_link'] ?? '',
                        'status' => 'Applied',
                        'notes' => 'Applied via student dashboard.',
                        'applied_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    set_flash_message('success', 'Application submitted successfully for ' . htmlspecialchars($job['title']) . ' at ' . htmlspecialchars($job['company_name']) . '!');
                    header('Location: student.php#applications-section');
                    exit;
                }
            }
        }
    } catch (\Throwable $e) {
        set_flash_message('danger', 'Error submitting application: ' . $e->getMessage());
    }
}

// 4. Fetch Student's Existing Applications
$myApplications = [];
$appliedJobIds = [];
try {
    $myApplications = $applicationsCollection->find(['student_id' => $studentId], ['sort' => ['applied_at' => -1]]);
    foreach ($myApplications as $app) {
        $appliedJobIds[(string)$app['job_id']] = $app['status'];
    }
} catch (\Throwable $e) {}

// 5. Fetch Active Jobs & Filter by CGPA Eligibility
$allActiveJobs = [];
$eligibleJobs = [];
$ineligibleJobs = [];

try {
    $allActiveJobs = $jobsCollection->find(['status' => 'active'], ['sort' => ['created_at' => -1]]);
    foreach ($allActiveJobs as $job) {
        $minCgpa = (float)($job['min_cgpa'] ?? 0.0);
        if ($currentCgpa >= $minCgpa) {
            $eligibleJobs[] = $job;
        } else {
            $ineligibleJobs[] = $job;
        }
    }
} catch (\Throwable $e) {}

// Application status tallies
$shortlistedCount = 0;
$hiredCount = 0;
foreach ($myApplications as $app) {
    if (strtolower($app['status']) === 'shortlisted') $shortlistedCount++;
    if (strtolower($app['status']) === 'hired') $hiredCount++;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Welcome Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">
                <i class="bi bi-person-circle text-primary me-2"></i>Welcome, <?= htmlspecialchars($currentUser['name']) ?>
            </h1>
            <p class="text-muted small mb-0">Student Placement Command Center & Application Tracker</p>
        </div>
        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6">
                <i class="bi bi-star-fill text-warning me-1"></i> Current CGPA: <strong><?= number_format($currentCgpa, 2) ?></strong>
            </span>
        </div>
    </div>

    <!-- Quick Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="bi bi-award-fill"></i>
                </div>
                <div>
                    <div class="stat-value text-primary"><?= number_format($currentCgpa, 2) ?></div>
                    <div class="stat-label">Your CGPA</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <div>
                    <div class="stat-value text-success"><?= count($eligibleJobs) ?></div>
                    <div class="stat-label">Eligible Drives</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon secondary">
                    <i class="bi bi-send-check"></i>
                </div>
                <div>
                    <div class="stat-value text-secondary"><?= count($myApplications) ?></div>
                    <div class="stat-label">Total Applied</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <div>
                    <div class="stat-value text-warning"><?= $hiredCount > 0 ? $hiredCount . ' Offers!' : $shortlistedCount . ' Shortlisted' ?></div>
                    <div class="stat-label"><?= $hiredCount > 0 ? 'Hired / Placed' : 'In Pipeline' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Nav Tabs for Student Sections -->
    <ul class="nav nav-pills mb-4 gap-2" id="studentTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active px-4 py-2 fw-semibold" id="jobs-tab" data-bs-toggle="tab" data-bs-target="#jobs-panel" type="button" role="tab">
                <i class="bi bi-briefcase-fill me-1"></i> Eligible Job Board (<?= count($eligibleJobs) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-4 py-2 fw-semibold" id="applications-tab" data-bs-toggle="tab" data-bs-target="#applications-panel" type="button" role="tab">
                <i class="bi bi-list-check me-1"></i> My Applications (<?= count($myApplications) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-4 py-2 fw-semibold" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-panel" type="button" role="tab">
                <i class="bi bi-person-lines-fill me-1"></i> Profile Builder
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="studentTabContent">

        <!-- TAB 1: ELIGIBLE JOB BOARD -->
        <div class="tab-pane fade show active" id="jobs-panel" role="tabpanel">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="fw-bold mb-0">Filtered Job Opportunities</h4>
                    <p class="text-muted small mb-0">Showing companies where your CGPA (<?= number_format($currentCgpa, 2) ?>) meets or exceeds the minimum cutoff.</p>
                </div>
                <?php if ($currentCgpa == 0): ?>
                    <div class="alert alert-warning py-1 px-3 small mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> CGPA set to 0.0. Update your profile in the Profile Builder tab to unlock all eligible jobs.
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($eligibleJobs)): ?>
                <div class="card card-custom p-5 text-center text-muted">
                    <i class="bi bi-funnel fs-1 text-secondary mb-2"></i>
                    <h5 class="fw-bold">No Eligible Job Drives At This Time</h5>
                    <p class="small mb-3">Current drives require a higher CGPA threshold than <?= number_format($currentCgpa, 2) ?> or your profile needs updating.</p>
                    <div>
                        <button class="btn btn-outline-primary btn-sm" onclick="document.getElementById('profile-tab').click()">
                            <i class="bi bi-pencil-square me-1"></i> Update Your CGPA in Profile Builder
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($eligibleJobs as $job): ?>
                        <?php 
                            $jobIdStr = (string)$job['_id'];
                            $isApplied = isset($appliedJobIds[$jobIdStr]);
                            $appStatus = $isApplied ? $appliedJobIds[$jobIdStr] : null;
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-custom h-100 p-4 d-flex flex-column border-top border-4 border-primary">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-building me-1"></i><?= htmlspecialchars($job['company_name'] ?? 'Company') ?>
                                    </span>
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="bi bi-check-circle-fill me-1"></i>Eligible
                                    </span>
                                </div>

                                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($job['title']) ?></h5>
                                <p class="text-muted small flex-grow-1 mb-3" style="min-height: 48px;">
                                    <?= htmlspecialchars($job['description']) ?>
                                </p>

                                <div class="bg-light p-3 rounded-3 mb-3 small">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted"><i class="bi bi-mortarboard me-1 text-primary"></i>Min CGPA:</span>
                                        <span class="fw-bold"><?= number_format((float)($job['min_cgpa'] ?? 0), 1) ?> (Yours: <?= number_format($currentCgpa, 1) ?>)</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted"><i class="bi bi-cash-stack me-1 text-success"></i>Package:</span>
                                        <span class="fw-bold"><?= htmlspecialchars($job['ctc'] ?? 'Competitive') ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted"><i class="bi bi-geo-alt me-1 text-danger"></i>Location:</span>
                                        <span class="fw-semibold"><?= htmlspecialchars($job['location'] ?? 'Remote / On-site') ?></span>
                                    </div>
                                </div>

                                <div class="mt-auto">
                                    <?php if ($isApplied): ?>
                                        <div class="d-flex align-items-center justify-content-between p-2 bg-light border rounded-3">
                                            <span class="small fw-semibold text-muted">Status:</span>
                                            <span class="badge-status <?= strtolower($appStatus) ?>">
                                                <i class="bi bi-check-all me-1"></i><?= htmlspecialchars($appStatus) ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <form method="POST" action="student.php">
                                            <input type="hidden" name="action" value="apply_job">
                                            <input type="hidden" name="job_id" value="<?= htmlspecialchars($jobIdStr) ?>">
                                            <button type="submit" class="btn btn-primary-custom w-100" onclick="return confirm('Confirm application for <?= addslashes($job['title']) ?> at <?= addslashes($job['company_name']) ?>?')">
                                                <i class="bi bi-send-fill me-1"></i> 1-Click Apply
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Ineligible drives collapsable view -->
            <?php if (!empty($ineligibleJobs)): ?>
                <div class="mt-5 pt-3 border-top">
                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#ineligibleCollapse">
                        <i class="bi bi-eye me-1"></i> View Other Drives (Requires higher CGPA) (<?= count($ineligibleJobs) ?>)
                    </button>
                    <div class="collapse mt-3" id="ineligibleCollapse">
                        <div class="row g-3">
                            <?php foreach ($ineligibleJobs as $job): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card card-custom p-3 bg-light border-dashed opacity-75">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($job['title']) ?></h6>
                                            <span class="badge bg-danger-subtle text-danger small">Req: <?= number_format((float)$job['min_cgpa'], 1) ?> CGPA</span>
                                        </div>
                                        <div class="small text-muted mb-2"><?= htmlspecialchars($job['company_name']) ?></div>
                                        <small class="text-danger">
                                            <i class="bi bi-lock-fill me-1"></i>Your CGPA of <?= number_format($currentCgpa, 1) ?> does not meet this drive's cutoff.
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB 2: MY APPLICATIONS TRACKER -->
        <div class="tab-pane fade" id="applications-panel" role="tabpanel">
            <div class="card card-custom overflow-hidden">
                <div class="card-custom-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">Application Tracker</h5>
                        <p class="text-muted small mb-0">Real-time status updates from recruitment teams</p>
                    </div>
                    <span class="badge bg-primary rounded-pill"><?= count($myApplications) ?> Total Submissions</span>
                </div>

                <?php if (empty($myApplications)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-inbox fs-1 mb-2"></i>
                        <h6>No Applications Submitted Yet</h6>
                        <p class="small mb-3">Browse the Eligible Job Board tab and apply with one click.</p>
                        <button class="btn btn-primary-custom btn-sm" onclick="document.getElementById('jobs-tab').click()">
                            Browse Eligible Drives
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Job Role & Company</th>
                                    <th>Applied On</th>
                                    <th>Submitted CGPA</th>
                                    <th>Resume Portfolio</th>
                                    <th>Recruitment Status</th>
                                    <th>Company Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myApplications as $app): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($app['job_title'] ?? 'Role') ?></div>
                                            <small class="text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($app['company_name'] ?? 'Company') ?></small>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars(date('M d, Y', strtotime($app['applied_at'] ?? 'now'))) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= number_format((float)($app['student_cgpa'] ?? 0), 2) ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($app['resume_link'])): ?>
                                                <a href="<?= htmlspecialchars($app['resume_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.8rem;">
                                                    <i class="bi bi-file-earmark-pdf me-1"></i>View Link
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">Not attached</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                                $statusClass = strtolower($app['status'] ?? 'applied');
                                                $icon = 'bi-send';
                                                if ($statusClass === 'shortlisted') $icon = 'bi-star-fill text-warning';
                                                elseif ($statusClass === 'hired') $icon = 'bi-award-fill text-success';
                                                elseif ($statusClass === 'rejected') $icon = 'bi-x-circle text-danger';
                                            ?>
                                            <span class="badge-status <?= $statusClass ?>">
                                                <i class="bi <?= $icon ?> me-1"></i><?= htmlspecialchars($app['status'] ?? 'Applied') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($app['notes'] ?? 'Under review by recruitment team') ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB 3: PROFILE BUILDER -->
        <div class="tab-pane fade" id="profile-panel" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card card-custom p-4">
                        <div class="d-flex align-items-center gap-3 mb-4 pb-2 border-bottom">
                            <div class="stat-icon primary">
                                <i class="bi bi-mortarboard fs-3"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Student Profile Builder</h5>
                                <p class="text-muted small mb-0">Your CGPA directly controls your job board eligibility</p>
                            </div>
                        </div>

                        <form method="POST" action="student.php">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="prof-name" class="form-label">Full Name</label>
                                    <input type="text" name="name" id="prof-name" class="form-control" value="<?= htmlspecialchars($profile['name'] ?? $currentUser['name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address (Registered)</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($currentUser['email']) ?>" readonly>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="prof-cgpa" class="form-label text-primary fw-bold">
                                        <i class="bi bi-star-fill text-warning me-1"></i> Cumulative CGPA (Scale 0.0 - 10.0) *
                                    </label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0" max="10.0" name="cgpa" id="prof-cgpa" class="form-control fw-bold fs-5 text-primary" value="<?= htmlspecialchars((string)$currentCgpa) ?>" required>
                                        <span class="input-group-text bg-light">/ 10.0</span>
                                    </div>
                                    <small class="form-text text-muted">Example: 8.5 or 7.2. Updates eligible drives immediately.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="prof-dept" class="form-label">Department / Branch</label>
                                    <input type="text" name="department" id="prof-dept" class="form-control" value="<?= htmlspecialchars($profile['department'] ?? 'Computer Science') ?>" placeholder="e.g. Computer Science & Eng.">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="prof-phone" class="form-label">Phone Number</label>
                                    <input type="text" name="phone" id="prof-phone" class="form-control" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" placeholder="+1 (555) 000-0000">
                                </div>
                                <div class="col-md-6">
                                    <label for="prof-grad" class="form-label">Graduation Year</label>
                                    <input type="text" name="graduation_year" id="prof-grad" class="form-control" value="<?= htmlspecialchars($profile['graduation_year'] ?? date('Y')+1) ?>" placeholder="e.g. 2027">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="prof-skills" class="form-label">Technical Skills (Comma Separated)</label>
                                <input type="text" name="skills" id="prof-skills" class="form-control" value="<?= htmlspecialchars($profile['skills'] ?? '') ?>" placeholder="PHP, MongoDB, React, Node.js, Python, Git">
                                <small class="text-muted">Showcase your programming languages, frameworks, and tools.</small>
                            </div>

                            <div class="mb-3">
                                <label for="prof-resume" class="form-label text-dark fw-bold">
                                    <i class="bi bi-link-45deg me-1"></i> Resume / Portfolio Link (Google Drive / GitHub / LinkedIn) *
                                </label>
                                <input type="url" name="resume_link" id="prof-resume" class="form-control" value="<?= htmlspecialchars($profile['resume_link'] ?? '') ?>" placeholder="https://drive.google.com/your-resume.pdf" required>
                                <small class="text-muted">Must be a public URL accessible by recruiting companies.</small>
                            </div>

                            <div class="mb-4">
                                <label for="prof-bio" class="form-label">Professional Summary / Bio</label>
                                <textarea name="bio" id="prof-bio" rows="3" class="form-control" placeholder="Brief summary of your academic strengths, career ambitions, and projects..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary-custom px-4 py-2">
                                    <i class="bi bi-save me-1"></i> Save & Refresh Eligibility
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Profile Preview Card -->
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="card card-custom p-4 bg-light border-0">
                        <div class="text-center mb-3">
                            <div class="stat-icon primary mx-auto mb-2" style="width: 70px; height: 70px; border-radius: 50%;">
                                <i class="bi bi-person-badge fs-2"></i>
                            </div>
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($profile['name'] ?? $currentUser['name']) ?></h5>
                            <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($profile['department'] ?? 'Student') ?></span>
                        </div>

                        <div class="p-3 bg-white rounded-3 shadow-sm mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small text-muted">Academic CGPA</span>
                                <span class="badge bg-success fs-6"><?= number_format($currentCgpa, 2) ?></span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, $currentCgpa * 10) ?>%"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted text-uppercase d-block mb-1">Key Skills</label>
                            <div class="d-flex flex-wrap gap-1">
                                <?php 
                                    $skillsArr = array_filter(array_map('trim', explode(',', $profile['skills'] ?? '')));
                                    if (empty($skillsArr)): ?>
                                        <span class="text-muted small">No skills entered yet</span>
                                    <?php else:
                                        foreach ($skillsArr as $skill): ?>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($skill) ?></span>
                                        <?php endforeach;
                                    endif;
                                ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted text-uppercase d-block mb-1">Resume Attached</label>
                            <?php if (!empty($profile['resume_link'])): ?>
                                <a href="<?= htmlspecialchars($profile['resume_link']) ?>" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Open Resume Link
                                </a>
                            <?php else: ?>
                                <span class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i>No link configured</span>
                            <?php endif; ?>
                        </div>

                        <div class="text-muted small border-top pt-2 mt-2">
                            <span><i class="bi bi-clock me-1"></i>Last updated: <?= htmlspecialchars(date('M d, Y H:i', strtotime($profile['updated_at'] ?? 'now'))) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
