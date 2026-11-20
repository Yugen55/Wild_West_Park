<?php

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db.php';
$bdd = getDB();

$message = null;

if (isset($_POST['ok'])) {
    $titre         = $_POST['titre'] ?? '';
    $description   = $_POST['description'] ?? '';
    $date_sortie   = $_POST['date_sortie'] ?? '';
    $nb_places_max = $_POST['nb_places_max'] ?? '';
    $id_utilisateur = $_SESSION['id_utilisateur'] ?? null;

    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $nom_fichier  = uniqid() . '_' . basename($_FILES['image']['name']);
        $dossier_dest = __DIR__ . '/../images/';

        if (!is_dir($dossier_dest)) {
            mkdir($dossier_dest, 0755, true);
        }

        if (move_uploaded_file($_FILES['image']['tmp_name'], $dossier_dest . $nom_fichier)) {
            $image = $nom_fichier;
        }
    }

    try {
        $sql = "INSERT INTO communaute (titre, description, date_sortie, nb_places_max, image_communaute, id_createur)
        VALUES (:titre, :description, :date_sortie, :nb_places_max, :image, :id_utilisateur)";
        $requete = $bdd->prepare($sql);
        $requete->execute([
            ":titre"          => $titre,
            ":description"    => $description,
            ":date_sortie"    => $date_sortie,
            ":nb_places_max"  => $nb_places_max,
            ":image"          => $image,
            ":id_utilisateur" => $id_utilisateur,
        ]);

        $id_communaute = $bdd->lastInsertId();

        header("Location: ../communaute/communaute_detail.php?id=" . $id_communaute);
        exit();

    } catch (Exception $e) {
        die("Erreur SQL : " . $e->getMessage());
    }
}