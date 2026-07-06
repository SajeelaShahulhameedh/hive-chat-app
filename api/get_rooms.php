<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_name   = mysqli_real_escape_string($conn, trim($_POST['room_name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));

    if (empty($room_name)) {
        header("Location: /hive/dashboard.php?error=Room+name+is+required");
        exit();
    }

    $check = mysqli_query($conn, "SELECT room_id FROM rooms WHERE room_name='$room_name'");
    if (mysqli_num_rows($check) > 0) {
        header("Location: /hive/dashboard.php?error=Room+name+already+exists");
        exit();
    }

    $sql = "INSERT INTO rooms (room_name, description, created_by)
            VALUES ('$room_name', '$description', {$user['user_id']})";

    if (mysqli_query($conn, $sql)) {
        $room_id = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO room_members (room_id, user_id, role)
                             VALUES ($room_id, {$user['user_id']}, 'admin')");
        header("Location: /hive/dashboard.php?created=1");
    } else {
        header("Location: /hive/dashboard.php?error=Something+went+wrong");
    }
    exit();
}

header("Location: /hive/dashboard.php");
exit();
?>