<?php
require_once __DIR__ . '/db.php';
header('Content-Type: text/plain');
print_r(get_db_status());
