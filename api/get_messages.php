<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();
$user    = getCurrentUser();
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;

if (!$room_id) {
    echo json_encode([]);
    exit();
}

// Auto join room if not member
$check = mysqli_query($conn, "SELECT member_id FROM room_members 
                               WHERE room_id=$room_id AND user_id={$user['user_id']}");
if (mysqli_num_rows($check) === 0) {
    mysqli_query($conn, "INSERT INTO room_members (room_id, user_id, role)
                         VALUES ($room_id, {$user['user_id']}, 'member')");
}

// Mark messages as seen
mysqli_query($conn, "INSERT IGNORE INTO message_seen (message_id, user_id)
    SELECT m.message_id, {$user['user_id']}
    FROM messages m
    WHERE m.room_id = $room_id
    AND m.sender_id != {$user['user_id']}
    AND m.is_deleted = 0");

// Fetch messages
$since = isset($_GET['since']) ? mysqli_real_escape_string($conn, $_GET['since']) : '0';

$sql = "SELECT m.*,
               u.username,
               u.profile_pic,
               (SELECT COUNT(*) FROM message_seen ms 
                WHERE ms.message_id = m.message_id) as seen_count,
               (SELECT GROUP_CONCAT(emoji) FROM reactions r 
                WHERE r.message_id = m.message_id) as reactions,
               rm.content as reply_content,
               ru.username as reply_username
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        LEFT JOIN messages rm ON m.reply_to = rm.message_id
        LEFT JOIN users ru ON rm.sender_id = ru.user_id
        WHERE m.room_id = $room_id
        AND m.is_deleted = 0
        AND m.message_id > '$since'
        ORDER BY m.sent_at ASC
        LIMIT 50";

$result   = mysqli_query($conn, $sql);
$messages = [];

while ($row = mysqli_fetch_assoc($result)) {
    $row['is_mine']    = ($row['sender_id'] == $user['user_id']);
    $row['time_label'] = formatTime($row['sent_at']);
    $row['avatar_url'] = '/hive/assets/uploads/avatars/' . $row['profile_pic'];
    $messages[]        = $row;
}

header('Content-Type: application/json');
echo json_encode($messages);
?>