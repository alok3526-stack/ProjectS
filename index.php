<?php
$pageTitle = 'Home | CampusHire Placement Portal';
$activeNav = 'home';

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';

// Fetch public stats
$studentCount = 0;
$companyCount = 0;
$jobCount = 0;
$applicationCount = 0;
$recentJobs = [];

try {
    $studentCount = $usersCollection->countDocuments(['role' => 'student']);
    $companyCount = $usersCollection->countDocuments(['role' => 'company']);
    $jobCount = $jobsCollection->countDocuments(['status' => 'active']);
    $applicationCount = $applicationsCollection->countDocuments([]);
    $recentJobs = $jobsCollection->find(['status' => 'active'], ['limit' => 6, 'sort' => ['created_at' => -1]]);
} catch (\Throwable $e) {
    // Graceful fallback
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="py-5 hero-gradient border-bottom">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill mb-3">
                    <i class="bi bi-stars me-1"></i> Smart Placement & Recruitment Platform
                </span>
                <h1 class="display-4 fw-extrabold text-dark mb-3" style="line-height: 1.15;">
                    Accelerating Campus Careers. <br>
                    <span class="text-primary">Bridging Talent with Opportunity.</span>
                </h1>
                <p class="lead text-muted mb-4">
                    The modern full-stack Placement Management System. Empowering students with automated CGPA eligibility filtering, enabling companies to manage recruitment drives, and providing campus administrators with verification and governance tools.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <?php if ($currentUser): ?>
                        <a href="/dashboard/<?= htmlspecialchars($currentUser['role']) ?>.php" class="btn btn-primary-custom btn-lg">
                            <i class="bi bi-speedometer2 me-2"></i> Open <?= ucfirst(htmlspecialchars($currentUser['role'])) ?> Dashboard
                        </a>
                    <?php else: ?>
                        <a href="/register.php" class="btn btn-primary-custom btn-lg">
                            <i class="bi bi-rocket-takeoff-fill me-2"></i> Get Started as Student / Company
                        </a>
                        <a href="/login.php" class="btn btn-outline-custom btn-lg">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Existing User Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card card-custom p-4 border-0 shadow-lg bg-white position-relative">
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon primary" style="width: 44px; height: 44px;">
                                <i class="bi bi-activity fs-5"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">Live Placement Metrics</h6>
                                <small class="text-muted">Real-time NoSQL Aggregations</small>
                            </div>
                        </div>
                        <span class="badge bg-success-subtle text-success">Live</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <div class="text-primary fw-bold fs-3"><?= (int)$studentCount ?></div>
                                <div class="text-muted small text-uppercase fw-semibold">Students</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <div class="text-info fw-bold fs-3"><?= (int)$companyCount ?></div>
                                <div class="text-muted small text-uppercase fw-semibold">Companies</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <div class="text-success fw-bold fs-3"><?= (int)$jobCount ?></div>
                                <div class="text-muted small text-uppercase fw-semibold">Active Drives</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <div class="text-warning fw-bold fs-3"><?= (int)$applicationCount ?></div>
                                <div class="text-muted small text-uppercase fw-semibold">Applications</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top text-center">
                        <a href="/login.php" class="text-primary small fw-semibold text-decoration-none">
                            <i class="bi bi-lightning-charge me-1"></i>Try Instant Demo Login (Alex / Google / Admin) &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Active Job Drives Preview -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="text-primary fw-bold text-uppercase small tracking-wide">Featured Drives</span>
                <h2 class="h3 fw-bold mb-0">Current Campus Hiring Drives</h2>
            </div>
            <div>
                <?php if ($currentUser && $currentUser['role'] === 'student'): ?>
                    <a href="/dashboard/student.php" class="btn btn-outline-primary btn-sm">
                        View Eligible Drives <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                <?php else: ?>
                    <a href="/register.php" class="btn btn-outline-primary btn-sm">
                        Register to Apply <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($recentJobs)): ?>
            <div class="card card-custom p-5 text-center text-muted">
                <i class="bi bi-briefcase fs-1 mb-2"></i>
                <h5>No Active Job Drives Yet</h5>
                <p class="small mb-0">Partner companies can register and post hiring drives instantly.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($recentJobs as $job): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-custom h-100 p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-light text-dark border mb-2">
                                        <i class="bi bi-building me-1"></i><?= htmlspecialchars($job['company_name'] ?? 'Company') ?>
                                    </span>
                                    <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($job['title']) ?></h5>
                                </div>
                                <span class="badge bg-success-subtle text-success">Active</span>
                            </div>

                            <p class="text-muted small flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= htmlspecialchars($job['description']) ?>
                            </p>

                            <div class="border-top pt-3 mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                                    <span><i class="bi bi-award me-1 text-primary"></i>Min CGPA:</span>
                                    <span class="fw-bold text-dark"><?= number_format((float)($job['min_cgpa'] ?? 0), 1) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                                    <span><i class="bi bi-cash-stack me-1 text-success"></i>Package:</span>
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($job['ctc'] ?? 'Competitive') ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center small text-muted">
                                    <span><i class="bi bi-geo-alt me-1 text-danger"></i>Location:</span>
                                    <span class="text-dark"><?= htmlspecialchars($job['location'] ?? 'Flexible') ?></span>
                                </div>
                            </div>

                            <div class="mt-3 pt-2">
                                <?php if ($currentUser && $currentUser['role'] === 'student'): ?>
                                    <a href="/dashboard/student.php" class="btn btn-primary-custom w-100 btn-sm">
                                        <i class="bi bi-send me-1"></i> Apply from Dashboard
                                    </a>
                                <?php else: ?>
                                    <a href="/login.php" class="btn btn-outline-primary w-100 btn-sm">
                                        Sign In to Apply
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- System Feature Highlights -->
<section class="py-5 bg-white border-top">
    <div class="container py-3">
        <div class="text-center max-w-2xl mx-auto mb-5">
            <span class="text-primary fw-bold text-uppercase small">Architected for Campuses</span>
            <h2 class="h3 fw-bold">Comprehensive Placement Ecosystem</h2>
            <p class="text-muted small">Designed specifically for student success, company hiring velocity, and administrative governance.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-custom h-100 p-4 border-0 bg-light">
                    <div class="stat-icon primary mb-3">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <h5 class="fw-bold">Student Hub</h5>
                    <p class="text-muted small mb-0">Build your dynamic academic profile, specify your CGPA and portfolio resume, and view only the jobs where you meet the academic eligibility threshold. 1-click apply and real-time ATS status tracker.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom h-100 p-4 border-0 bg-light">
                    <div class="stat-icon success mb-3">
                        <i class="bi bi-buildings-fill"></i>
                    </div>
                    <h5 class="fw-bold">Company ATS</h5>
                    <p class="text-muted small mb-0">Create new campus hiring drives with customized CGPA cutoffs, CTC compensation, and descriptions. Review incoming student applications and seamlessly transition candidates: Applied &rarr; Shortlisted &rarr; Hired.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom h-100 p-4 border-0 bg-light">
                    <div class="stat-icon warning mb-3">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h5 class="fw-bold">Admin Governance</h5>
                    <p class="text-muted small mb-0">Full oversight for campus placement coordinators. Verify and approve student and company accounts, review drive activities, monitor overall placement rates, and ensure institutional compliance.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
