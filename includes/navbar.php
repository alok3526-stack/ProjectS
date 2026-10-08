<?php
$currentRole = $currentUser['role'] ?? null;
?>
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/">
            <i class="bi bi-mortarboard-fill"></i>
            <span>Campus<span class="text-primary">Hire</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?= empty($activeNav) || $activeNav === 'home' ? 'active' : '' ?>" href="/">
                        <i class="bi bi-house me-1"></i> Home
                    </a>
                </li>
                <?php if ($currentUser): ?>
                    <?php if ($currentRole === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($activeNav ?? '') === 'admin' ? 'active' : '' ?>" href="/dashboard/admin.php">
                                <i class="bi bi-speedometer2 me-1"></i> Admin Portal
                            </a>
                        </li>
                    <?php elseif ($currentRole === 'company'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($activeNav ?? '') === 'company' ? 'active' : '' ?>" href="/dashboard/company.php">
                                <i class="bi bi-briefcase me-1"></i> Recruitment Portal
                            </a>
                        </li>
                    <?php elseif ($currentRole === 'student'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($activeNav ?? '') === 'student' ? 'active' : '' ?>" href="/dashboard/student.php">
                                <i class="bi bi-person-workspace me-1"></i> Student Portal
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($currentUser): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-custom dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5 text-primary"></i>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($currentUser['name']) ?></span>
                            <span class="badge bg-primary text-uppercase" style="font-size: 0.65rem;"><?= htmlspecialchars($currentUser['role']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                            <li><h6 class="dropdown-header"><?= htmlspecialchars($currentUser['email']) ?></h6></li>
                            <?php if ($currentRole === 'admin'): ?>
                                <li><a class="dropdown-item" href="/dashboard/admin.php"><i class="bi bi-shield-check me-2"></i>Admin Dashboard</a></li>
                            <?php elseif ($currentRole === 'company'): ?>
                                <li><a class="dropdown-item" href="/dashboard/company.php"><i class="bi bi-building me-2"></i>Company Drives</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="/dashboard/student.php"><i class="bi bi-person-badge me-2"></i>Profile & Jobs</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="/login.php" class="btn btn-outline-custom">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Log In
                    </a>
                    <a href="/register.php" class="btn btn-primary-custom">
                        <i class="bi bi-person-plus me-1"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
