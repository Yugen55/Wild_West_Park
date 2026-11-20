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
    header('Location: 404.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ok'])) {

    $option_choisie = intval($_POST['option_choisie'] ?? 0);

    if ($option_choisie <= 0) {
        header('Location: communaute_chat.php?id=' . $id_communaute);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM communaute_votes WHERE id_utilisateur = ? AND id_communaute = ?");
    $stmt->execute([$_SESSION['id_utilisateur'], $id_communaute]);
    $a_vote = $stmt->fetch();

    if ($a_vote) {
        $stmt = $pdo->prepare("UPDATE communaute_votes SET option_choisie = ?, date_vote = NOW() WHERE id_communaute = ? AND id_utilisateur = ?");
        $stmt->execute([$option_choisie, $id_communaute, $_SESSION['id_utilisateur']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO communaute_votes(id_communaute, id_utilisateur, option_choisie, date_vote) VALUES(?, ?, ?, NOW())");
        $stmt->execute([$id_communaute, $_SESSION['id_utilisateur'], $option_choisie]);
    }

    header('Location: communaute_chat.php?id=' . $id_communaute);
    exit;
}

header('Location: communaute_chat.php?id=' . $id_communaute);
exit;
?>