<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireLogin();
$user       = getCurrentUser();
$message_id = (int)($_POST['message_id'] ?? 0);
$emoji      = mysqli_real_escape_string($conn, trim($_POST['emoji'] ?? ''));

if ($message_id && $emoji) {
    mysqli_query($conn, "INSERT INTO reactions (message_id, user_id, emoji)
                         VALUES ($message_id, {$user['user_id']}, '$emoji')
                         ON DUPLICATE KEY UPDATE emoji='$emoji'");
}

echo json_encode(['success' => true]);
?>