<?php
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;

    if ($diff < 60)         return "just now";
    if ($diff < 3600)       return floor($diff / 60) . " min ago";
    if ($diff < 86400)      return floor($diff / 3600) . " hrs ago";
    if ($diff < 604800)     return floor($diff / 86400) . " days ago";
    return date("d M Y", $time);
}

function formatTime($timestamp) {
    return date("h:i A", strtotime($timestamp));
}

function getAvatar($profile_pic) {
    $path = "/hive/assets/uploads/avatars/" . $profile_pic;
    if ($profile_pic === 'default_avatar.png' || !file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
        return "/hive/assets/uploads/avatars/default_avatar.png";
    }
    return $path;
}
?>