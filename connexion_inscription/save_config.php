<?php
session_start();

if (!isset($_SESSION['id_role']) || $_SESSION['id_role'] != 2) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

$duration = isset($_POST['duration']) ? (int) $_POST['duration'] : 30;

$allowed = [5, 10, 15, 30, 60];
if (!in_array($duration, $allowed)) {
    $duration = 30;
}

file_put_contents(
    $_SERVER['DOCUMENT_ROOT'] . '/config.json',
    json_encode(['session_timeout' => $duration])
);

echo json_encode(['success' => true, 'duration' => $duration]);
?>