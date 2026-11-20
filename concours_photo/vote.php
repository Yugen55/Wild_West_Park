<?php
session_start();

require '../session.php';
require '../db.php';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /concours_photo/concours_photo.php');
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'] ?? null;
if ($id_utilisateur === null) {
    header('Location: /connexion.php');
    exit;
}

$id_concours = (int) ($_POST['id_concours'] ?? 0);
$id_participation = (int) ($_POST['id_participation'] ?? 0);

if ($id_concours <= 0 || $id_participation <= 0) {
    header('Location: /concours_photo/concours_photo.php?erreur=vote_invalide');
    exit;
}

$stmt = $pdo->prepare("SELECT statut FROM concours_photo WHERE id_concours = ?");
$stmt->execute([$id_concours]);
$concours = $stmt->fetch();

if (!$concours || $concours['statut'] !== 'en_cours') {
    header('Location: /concours_photo/concours_photo.php?erreur=vote_ferme');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id_utilisateur FROM participation_concours
    WHERE id_participation = ? AND id_concours = ?
");
$stmt->execute([$id_participation, $id_concours]);
$participation = $stmt->fetch();

if (!$participation) {
    header('Location: /concours_photo/concours_photo.php?erreur=photo_invalide');
    exit;
}

if ((int) $participation['id_utilisateur'] === $id_utilisateur) {
    header('Location: /concours_photo/concours_photo.php?erreur=vote_propre_photo');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id_participation FROM vote_photo
    WHERE id_concours = ? AND id_utilisateur = ?
");
$stmt->execute([$id_concours, $id_utilisateur]);
$vote_existant = $stmt->fetch();

if ($vote_existant) {
    $stmt = $pdo->prepare("
        UPDATE vote_photo
        SET id_participation = ?
        WHERE id_concours = ? AND id_utilisateur = ?
    ");
    $stmt->execute([$id_participation, $id_concours, $id_utilisateur]);
} else {
    $stmt = $pdo->prepare("
        INSERT INTO vote_photo (id_concours, id_participation, id_utilisateur)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$id_concours, $id_participation, $id_utilisateur]);
}

header('Location: /concours_photo/concours_main.php');
exit;