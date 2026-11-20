<?php

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db.php';
$bdd = getDB();

$message = null;

if (isset($_POST['ok'])) {
    $nom        = $_POST['nom'] ?? '';
    $description        = $_POST['description'] ?? '';
    $age_minimum        = $_POST['age_minimum'] ?? '';
    $taille_minimum     = $_POST['taille_minimum'] ?? '';
    $places_disponibles = $_POST['places_disponibles'] ?? '';
    $duree_minutes      = $_POST['duree_minutes'] ?? '';
    $prix = $_POST['prix'] ?? '';
    $statut             = 'ouverte';
    $heure_ouverture      = $_POST['heure_ouverture'] ?? '';
    $heure_fermeture      = $_POST['heure_fermeture'] ?? '';

    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $nom_fichier  = uniqid() . '_' . basename($_FILES['image_bien']['name']);
        $dossier_dest = __DIR__ . '/../images/';

        if (!is_dir($dossier_dest)) {
            mkdir($dossier_dest, 0755, true);
        }

        if (move_uploaded_file($_FILES['image']['tmp_name'], $dossier_dest . $nom_fichier)) {
            $image = $nom_fichier;
        }
    }

    try {
        $sql = "INSERT INTO attraction (nom, description, age_minimum, taille_minimum, places_disponibles, duree_minutes, statut, image, prix, heure_ouverture, heure_fermeture)
        VALUES (:nom, :description, :age_minimum, :taille_minimum, :places_disponibles, :duree_minutes, :statut, :image, :prix, :heure_ouverture, :heure_fermeture)";
        $requete = $bdd->prepare($sql);
        $requete->execute([
            ":nom"        => $nom,
            ":description"        => $description,
            ":age_minimum"        => $age_minimum,
            ":taille_minimum"     => $taille_minimum,
            ":places_disponibles" => $places_disponibles,
            ":duree_minutes"      => $duree_minutes,
            ":statut"             => $statut,
            ":image"              => $image,
            ":prix"              => $prix,
            ":heure_ouverture"              => $heure_ouverture,
            ":heure_fermeture"              => $heure_fermeture,
        ]);

        $id_attraction = $bdd->lastInsertId();

        header("Location: ../attraction.php?id=" . $id_attraction);
        exit();

    } catch (Exception $e) {
        die("Erreur SQL : " . $e->getMessage());
    }
}