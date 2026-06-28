<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /hive/login.php");
        exit();
    }
}

function getCurrentUser() {
    return [
        'user_id'     => $_SESSION['user_id'],
        'username'    => $_SESSION['username'],
        'profile_pic' => $_SESSION['profile_pic'],
    ];
}
?>