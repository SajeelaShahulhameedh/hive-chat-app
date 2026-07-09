<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireLogin();
$user   = getCurrentUser();
$status = $_POST['status'] ?? 'online';

$allowed = ['online', 'away', 'busy', 'offline'];
if (!in_array($status, $allowed)) $status = 'online';

mysqli_query($conn, "UPDATE users SET user_status='$status', last_seen=NOW() 
                     WHERE user_id={$user['user_id']}");

echo json_encode(['success' => true]);
?>