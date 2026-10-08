<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

logout_user();
header('Location: /login.php?logout=1');
exit;
