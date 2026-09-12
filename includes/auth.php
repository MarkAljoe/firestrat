<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'        => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['role'],
    ];
}
