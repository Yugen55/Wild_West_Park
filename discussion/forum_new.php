<?php
session_start();
require '../db.php';
$conn = getDB();

if (!isset($_SESSION['id_utilisateur'])) {
    die("Vous devez vous connecter");
}

$id_attraction = (int)($_GET['id_attraction'] ?? $_POST['id_attraction'] ?? 0);

if ($id_attraction <= 0) {
    die("Aucune attraction sélectionnée.");
}

$stmtAttraction = $conn->prepare("SELECT nom FROM attraction WHERE id_attraction = :id");
$stmtAttraction->execute([':id' => $id_attraction]);
$attraction = $stmtAttraction->fetch(PDO::FETCH_ASSOC);

if (!$attraction) {
    die("Attraction introuvable.");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');

    if ($titre === '' || $contenu === '') {
        $error = "Remplis tous les champs.";
    } elseif (mb_strlen($titre) > 255) {
        $error = "Le titre est trop long.";
    } else {
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("
                INSERT INTO sujet_forum (id_attraction, id_utilisateur, titre)
                VALUES (:id_attraction, :id_utilisateur, :titre)
            ");
            $stmt->execute([
                ':id_attraction' => $id_attraction,
                ':id_utilisateur' => $_SESSION['id_utilisateur'],
                ':titre' => $titre
            ]);

            $id_sujet = $conn->lastInsertId();

            $stmt2 = $conn->prepare("
                INSERT INTO message_forum (id_sujet, id_utilisateur, contenu)
                VALUES (:id_sujet, :id_utilisateur, :contenu)
            ");
            $stmt2->execute([
                ':id_sujet' => $id_sujet,
                ':id_utilisateur' => $_SESSION['id_utilisateur'],
                ':contenu' => $contenu
            ]);

            $conn->commit();

            header("Location: forum_thread.php?id_sujet=$id_sujet");
            exit;

        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "Une erreur est survenue.". $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouveau sujet - <?= htmlspecialchars($attraction['nom']) ?> - Wild West Park</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/CSS/style.css?v=<? filemtime('style.css'); ?>">
</head>
<body>

<div class="forum-container">

    <div class="forum-header">
        <h1>Nouveau sujet</h1>
        <p><?= htmlspecialchars($attraction['nom']) ?></p>
    </div>

    <div class="top-actions">
        <a href="forum.php?id_attraction=<?= htmlspecialchars($id_attraction) ?>" class="btn btn-secondary btn-custom">
            ← Retour au forum
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="id_attraction" value="<?= htmlspecialchars($id_attraction) ?>">

        <input 
            type="text" 
            name="titre" 
            class="form-control search-box mb-3"
            placeholder="Titre du sujet"
            maxlength="255"
            required
        >

        <textarea 
            name="contenu" 
            class="form-control search-box mb-3"
            rows="6"
            placeholder="Écris ton message..."
            required
        ></textarea>

        <button class="btn btn-primary btn-custom">Publier</button>
    </form>

</div>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php') ?>
</body>
</html>