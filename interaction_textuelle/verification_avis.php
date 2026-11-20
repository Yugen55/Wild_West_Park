<?php 
require '../session.php';
require '../db.php';
$pdo = getDB();

$id_utilisateur = $_SESSION['id_utilisateur'] ?? NULL;

$id_attraction_actuelle = $_GET['id'];

if (isset($_POST['ok'])) {
    $note = $_POST['note'] ?? '5';
    $message = $_POST['commentaire'] ?? NULL;
    $date_avis = date('Y-m-d');
}

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



$stmt=$pdo->prepare("INSERT INTO avis(id_utilisateur, id_attraction, note, message, date_avis, image) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$id_utilisateur, $id_attraction_actuelle, $note, $message, $date_avis, $image]);

header("Location: ../attraction.php?id=$id_attraction_actuelle");
    
?>