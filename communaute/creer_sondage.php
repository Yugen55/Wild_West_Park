<?php 

ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../session.php';
require '../db.php';
$pdo = getDB();

$id_communaute = intval($_GET['id_communaute'] ?? 0);


$stmt = $pdo->prepare("SELECT * FROM membre_communaute WHERE id_utilisateur = ? AND id_communaute = ?");
$stmt->execute([$_SESSION['id_utilisateur'], $id_communaute]);
$est_membre = $stmt->fetch();

if (!$est_membre) {
    header('Location: ../404.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ok'])) {

    $question = trim($_POST['question'] ?? '');
    $options = $_POST['options'] ?? [];

    $options = array_filter(array_map('trim', $options));

    if ($question === '') {
        $erreur = "La question ne peut pas être vide";
    } elseif (count($options) < 2) {
        $erreur = "Il faut au moins 2 options";
    } else {
        $stmt = $pdo->prepare("INSERT INTO sondage (id_communaute, question, date_creation) VALUES (?, ?, NOW())");
        $stmt->execute([$id_communaute, $question]);
        $id_sondage = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO option_sondage (id_sondage, libelle) VALUES (?, ?)");
        foreach ($options as $option) {
            $stmt->execute([$id_sondage, $option]);
        }

        header('Location: communaute_chat.php?id=' . $id_communaute);
        exit;
    }
}

?>