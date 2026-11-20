<?php
session_start();
require '../db.php';
$conn = getDB();

$id_sujet = (int)($_GET['id_sujet'] ?? 0);

if ($id_sujet <= 0) {
    die("Sujet non défini.");
}

$stmt = $conn->prepare("
    SELECT s.*, u.nom, u.prenom, a.nom AS attraction_nom
    FROM sujet_forum s
    JOIN utilisateur u ON s.id_utilisateur = u.id_utilisateur
    JOIN attraction a ON s.id_attraction = a.id_attraction
    WHERE s.id_sujet = :id
");
$stmt->execute([':id' => $id_sujet]);
$sujet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sujet) {
    die("Sujet introuvable.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['id_utilisateur'])) {
    if ($sujet['ferme']) {
        $error = "Ce sujet est fermé, tu ne peux plus répondre.";
    } else {
        $contenu = trim($_POST['contenu'] ?? '');

        if ($contenu !== '') {
            $stmt = $conn->prepare("
                INSERT INTO message_forum (id_sujet, id_utilisateur, contenu)
                VALUES (:id_sujet, :id_utilisateur, :contenu)
            ");
            $stmt->execute([
                ':id_sujet' => $id_sujet,
                ':id_utilisateur' => $_SESSION['id_utilisateur'],
                ':contenu' => $contenu
            ]);

            header("Location: forum_thread.php?id_sujet=$id_sujet");
            exit;
        }
    }
}

$stmt = $conn->prepare("
    SELECT m.*, u.nom, u.prenom
    FROM message_forum m
    JOIN utilisateur u ON m.id_utilisateur = u.id_utilisateur
    WHERE m.id_sujet = :id AND m.statut = 'visible'
    ORDER BY m.date_message ASC
");
$stmt->execute([':id' => $id_sujet]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$premierMessage = $messages[0] ?? null;
$reponses = array_slice($messages, 1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($sujet['titre']) ?> - Wild West Park</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/CSS/style.css?v=<? filemtime('style.css'); ?>">
</head>
<body>

<div class="forum-container">

    <div class="top-actions">
        <a href="forum.php?id_attraction=<?= $sujet['id_attraction'] ?>" class="btn btn-secondary btn-custom">
            ← Retour au forum
        </a>
    </div>

    <div class="thread-card">
        <div class="thread-title">
            <?php if ($sujet['epingle']): ?>📌 <?php endif; ?>
            <?php if ($sujet['ferme']): ?>🔒 <?php endif; ?>
            <?= htmlspecialchars($sujet['titre']) ?>
        </div>

        <div class="thread-info">
            Par <?= htmlspecialchars($sujet['prenom'] . ' ' . $sujet['nom']) ?> •
            <?= htmlspecialchars($sujet['date_creation']) ?> •
            Attraction : <?= htmlspecialchars($sujet['attraction_nom']) ?>
        </div>

        <hr>

        <?php if ($premierMessage): ?>
            <p><?= nl2br(htmlspecialchars($premierMessage['contenu'])) ?></p>
        <?php endif; ?>
    </div>

    <div class="forum-header">
        <h1>Réponses</h1>
        <p><?= count($reponses) ?> réponse(s)</p>
    </div>

    <?php if (empty($reponses)): ?>
        <div class="empty-box">Aucune réponse pour le moment.</div>
    <?php endif; ?>

    <?php foreach ($reponses as $msg): ?>
        <div class="thread-card">
            <div class="thread-info">
                <strong><?= htmlspecialchars($msg['prenom'] . ' ' . $msg['nom']) ?></strong> •
                <?= htmlspecialchars($msg['date_message']) ?>
            </div>
            <p class="mt-3"><?= nl2br(htmlspecialchars($msg['contenu'])) ?></p>
        </div>
    <?php endforeach; ?>

    <?php if (isset($_SESSION['id_utilisateur']) && !$sujet['ferme']): ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-4">
            <textarea 
                name="contenu" 
                class="form-control search-box mb-3" 
                rows="5" 
                placeholder="Écris ta réponse..."
                required
            ></textarea>
            <button class="btn btn-primary btn-custom">Répondre</button>
        </form>
    <?php elseif ($sujet['ferme']): ?>
        <div class="alert alert-secondary mt-4">Ce sujet est fermé aux réponses.</div>
    <?php else: ?>
        <div class="alert alert-warning mt-4">Connecte-toi pour répondre.</div>
    <?php endif; ?>

</div>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php') ?>
</body>
</html>