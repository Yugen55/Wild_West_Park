<?php 
require '../db.php';

$pdo = getDB();

if (!isset($_GET['id'])) {
    header('Location: /backoffice/backoffice.php');
    exit;
}

$id = (int) $_GET['id'];

$sql = "UPDATE attraction SET statut = 'ouverte' WHERE id_attraction = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);

header('Location: /backoffice/backoffice.php');
exit;
?>
