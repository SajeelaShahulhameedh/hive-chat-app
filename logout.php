<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    mysqli_query($conn, "UPDATE users SET user_status='offline',
                         last_seen=NOW() WHERE user_id=$user_id");
}

session_start();
session_destroy();
header("Location: /hive/login.php");
exit();
?>