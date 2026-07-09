<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit();
}

$room_id  = (int)($_POST['room_id'] ?? 0);
$content  = trim($_POST['content'] ?? '');
$reply_to = !empty($_POST['reply_to']) ? (int)$_POST['reply_to'] : null;
$msg_type = 'text';
$media_url = null;

if (!$room_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid room']);
    exit();
}

// Handle image upload
if (isset($_FILES['media']) && $_FILES['media']['error'] === 0) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (in_array($_FILES['media']['type'], $allowed)) {
        $ext       = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
        $filename  = 'media_' . time() . '_' . rand(100,999) . '.' . $ext;
        $dest      = __DIR__ . '/../assets/uploads/media/' . $filename;
        if (move_uploaded_file($_FILES['media']['tmp_name'], $dest)) {
            $media_url = $filename;
            $msg_type  = 'image';
        }
    }
}

if (empty($content) && !$media_url) {
    echo json_encode(['success' => false, 'error' => 'Empty message']);
    exit();
}

$content   = mysqli_real_escape_string($conn, $content);
$reply_sql = $reply_to ? $reply_to : 'NULL';

$sql = "INSERT INTO messages (room_id, sender_id, reply_to, content, media_url, msg_type)
        VALUES ($room_id, {$user['user_id']}, $reply_sql, '$content',
                " . ($media_url ? "'$media_url'" : 'NULL') . ", '$msg_type')";

if (mysqli_query($conn, $sql)) {
    $msg_id = mysqli_insert_id($conn);

    // Notify other members
    $members = mysqli_query($conn, "SELECT user_id FROM room_members 
                                    WHERE room_id=$room_id 
                                    AND user_id != {$user['user_id']}");
    while ($m = mysqli_fetch_assoc($members)) {
        mysqli_query($conn, "INSERT INTO notifications (user_id, room_id, message_id)
                             VALUES ({$m['user_id']}, $room_id, $msg_id)");
    }

    echo json_encode(['success' => true, 'message_id' => $msg_id]);
} else {
    echo json_encode(['success' => false, 'error' => 'DB error']);
}
?>