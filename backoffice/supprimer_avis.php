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


$pdo->prepare("DELETE FROM avis WHERE id_avis = ?")->execute([$id]);


header('Location: /backoffice/backoffice.php');
exit;
