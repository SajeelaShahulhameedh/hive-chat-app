<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireLogin();
$user       = getCurrentUser();
$message_id = (int)($_POST['message_id'] ?? 0);

if ($message_id) {
    mysqli_query($conn, "UPDATE messages SET is_deleted=1
                         WHERE message_id=$message_id
                         AND sender_id={$user['user_id']}");
}

echo json_encode(['success' => true]);
?>