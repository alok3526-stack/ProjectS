<?php
/**
 * Authentication & Session Management Module
 */

require_once __DIR__ . '/../config.php';

function get_auth_user(): ?array {
    if (!empty($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? '',
            'status' => $_SESSION['user_status'] ?? 'pending'
        ];
    }
    return null;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        set_flash_message('warning', 'Please log in to access this page.');
        header('Location: ../login.php');
        exit;
    }
}

function require_role($roles): void {
    require_login();
    $roles = (array)$roles;
    $currentRole = $_SESSION['user_role'] ?? '';

    if (!in_array($currentRole, $roles, true)) {
        set_flash_message('danger', 'Unauthorized access for your account role.');
        redirect_to_dashboard($currentRole);
        exit;
    }
}

function login_user(array $user): void {
    $_SESSION['user_id'] = (string)($user['_id'] ?? $user['id']);
    $_SESSION['user_name'] = $user['name'] ?? 'User';
    $_SESSION['user_email'] = $user['email'] ?? '';
    $_SESSION['user_role'] = strtolower($user['role'] ?? 'student');
    $_SESSION['user_status'] = $user['status'] ?? 'pending';

    set_flash_message('success', 'Welcome back, ' . htmlspecialchars($_SESSION['user_name']) . '!');
    redirect_to_dashboard($_SESSION['user_role']);
}

function redirect_to_dashboard(string $role): void {
    switch (strtolower($role)) {
        case 'admin':
            header('Location: /dashboard/admin.php');
            break;
        case 'company':
            header('Location: /dashboard/company.php');
            break;
        case 'student':
        default:
            header('Location: /dashboard/student.php');
            break;
    }
    exit;
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function set_flash_message(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function get_flash_message(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
