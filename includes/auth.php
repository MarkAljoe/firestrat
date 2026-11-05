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

function requireRole(array $allowed): void {
    requireLogin();
    if (!in_array($_SESSION['role'] ?? '', $allowed, true)) {
        header('Location: index.php');
        exit;
    }
}

function hasRole(array $allowed): bool {
    return in_array($_SESSION['role'] ?? '', $allowed, true);
}