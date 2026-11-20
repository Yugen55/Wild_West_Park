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
if ($id_concours <= 0) {
    header('Location: /concours_photo/concours_photo.php?erreur=concours_invalide');
    exit;
}

$stmt = $pdo->prepare("SELECT statut FROM concours_photo WHERE id_concours = ?");
$stmt->execute([$id_concours]);
$concours = $stmt->fetch();

if (!$concours || $concours['statut'] !== 'en_cours') {
    header('Location: /concours_photo/concours_photo.php?erreur=concours_ferme');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id_participation FROM participation_concours
    WHERE id_concours = ? AND id_utilisateur = ?
");
$stmt->execute([$id_concours, $id_utilisateur]);
if ($stmt->fetch()) {
    header('Location: /concours_photo/concours_photo.php?erreur=deja_participe');
    exit;
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    header('Location: /concours_photo/concours_photo.php?erreur=upload');
    exit;
}

$fichier = $_FILES['photo'];

if ($fichier['size'] > 5 * 1024 * 1024) {
    header('Location: /concours_photo/concours_photo.php?erreur=trop_lourd');
    exit;
}

$infos_image = getimagesize($fichier['tmp_name']);
if ($infos_image === false) {
    header('Location: /concours_photo/concours_photo.php?erreur=pas_une_image');
    exit;
}

$types_autorises = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

$mime = $infos_image['mime'];
if (!isset($types_autorises[$mime])) {
    header('Location: /concours_photo/concours_photo.php?erreur=format_non_supporte');
    exit;
}

$extension = $types_autorises[$mime];
$nom_fichier = bin2hex(random_bytes(16)) . '.' . $extension;

$chemin_destination = $_SERVER['DOCUMENT_ROOT'] . '/images/' . $nom_fichier;

if (!move_uploaded_file($fichier['tmp_name'], $chemin_destination)) {
    header('Location: /concours_photo/concours_photo.php?erreur=enregistrement');
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO participation_concours (id_concours, id_utilisateur, photo)
    VALUES (?, ?, ?)
");
$stmt->execute([$id_concours, $id_utilisateur, $nom_fichier]);

header('Location: /concours_photo/concours_main.php');
exit;