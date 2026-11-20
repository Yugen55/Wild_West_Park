<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);



require '../db.php';

$pdo = getDB();

if (!isset($_GET['id'])) {
    header('Location: /backoffice/backoffice.php');
    exit;
}

$id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT image FROM attraction WHERE id_attraction = ?");
$stmt->execute([$id]);
$attraction = $stmt->fetch();


if (!$attraction) {
    header('Location: /backoffice/backoffice.php');
    exit;
}

if (!empty($attraction['image_attraction'])) {
    $chemin = '/var/www/html/images/' . $attraction['image_attraction'];
    if (file_exists($chemin) && is_writable($chemin)) {
        unlink($chemin);
    }
}

$pdo->prepare("DELETE FROM attraction WHERE id_attraction = ?")->execute([$id]);


header('Location: /backoffice/backoffice.php');
exit;
