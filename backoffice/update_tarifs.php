<?php
session_start();
require '../db.php';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        UPDATE parametres_tarif 
        SET prix_base = ?, 
            ratio_adulte = ?, 
            ratio_enfant = ?, 
            ratio_senior = ?,
            nb_tickets_lot = ?
        WHERE id = 1
    ");
    
    $stmt->execute([
        $_POST['prix_base'],
        $_POST['ratio_adulte'],
        $_POST['ratio_enfant'],
        $_POST['ratio_senior'],
        $_POST['nb_tickets_lot']
    ]);
    
    header('Location: backoffice.php');
    exit;
}
?>