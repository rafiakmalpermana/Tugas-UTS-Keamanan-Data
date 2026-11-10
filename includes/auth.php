<?php
function start_secure_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Strict',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }
}

function csrf_token(): string {
    start_secure_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(?string $token): bool {
    start_secure_session();
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}

function require_admin(): void {
    start_secure_session();
    if (empty($_SESSION['admin_key'])) {
        header('Location: login.php');
        exit;
    }
    if (time() - ($_SESSION['login_at'] ?? 0) > 1200) { // sesi 20 menit
        $_SESSION = [];
        session_destroy();
        header('Location: login.php');
        exit;
    }
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
