<?php 
require '../db.php';
require '../session.php';

$pdo = getDB();

$titre = $_POST['titre'];
$description = $_POST['description'];
$date_debut = $_POST['date_debut'];
$date_fin = $_POST['date_fin'];
$jetons_recompense = $_POST['jetons_recompense'];

$aujourdhui = date("Y-m-d");

if ($date_debut > $aujourdhui) {
    $statut = "a_venir";
} elseif ($date_fin < $aujourdhui) {
    $statut = "termine";
} else {
    $statut = "en_cours";
}
$stmt = $pdo->prepare("INSERT INTO concours_photo(titre, description, date_debut, date_fin, jetons_recompense, statut) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$titre, $description, $date_debut, $date_fin, $jetons_recompense, $statut]);

header('Location: concours_main.php');