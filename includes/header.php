<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth.php';

$currentUser = get_auth_user();
$flash = get_flash_message();
$dbStatus = get_db_status();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>
    <!-- Bootstrap 5.3 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom Design Stylesheet -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<!-- System Connection Bar -->
<div class="bg-dark text-white py-1 px-3 small border-bottom border-secondary">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <span class="badge <?= $dbStatus['is_connected'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                <i class="bi <?= $dbStatus['is_connected'] ? 'bi-check-circle-fill' : 'bi-hdd-network' ?> me-1"></i>
                <?= htmlspecialchars($dbStatus['driver']) ?>
            </span>
            <span class="text-white-50 d-none d-md-inline">
                Target DB: <code><?= htmlspecialchars($dbStatus['target_db']) ?></code>
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($dbStatus['is_placeholder']): ?>
                <span class="text-warning-emphasis small">
                    <i class="bi bi-info-circle me-1"></i>Atlas placeholder detected in <code>config.php</code>
                </span>
            <?php elseif ($dbStatus['is_connected']): ?>
                <span class="text-success small">
                    <i class="bi bi-cloud-check-fill me-1"></i>Connected to MongoDB Atlas
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/navbar.php'; ?>

<!-- Flash Message Notifications -->
<?php if ($flash): ?>
    <div class="container mt-3">
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : ($flash['type'] === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill') ?> me-2"></i>
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

<main class="py-4">
