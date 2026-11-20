<?php
session_start();
require '../db.php';
$conn = getDB();

$search = trim($_GET['search'] ?? '');
$id_attraction = (int)($_GET['id_attraction'] ?? 0);

if ($id_attraction <= 0) {
    die("Aucune attraction sélectionnée.");
}

$stmtAttraction = $conn->prepare("SELECT nom FROM attraction WHERE id_attraction = ?");
$stmtAttraction->execute([$id_attraction]);
$attraction = $stmtAttraction->fetch(PDO::FETCH_ASSOC);

if (!$attraction) {
    die("Attraction introuvable.");
}

try {
    $sql = "
        SELECT 
            s.id_sujet,
            s.titre,
            s.date_creation,
            s.epingle,
            s.ferme,
            u.nom,
            u.prenom,
            COUNT(m.id_message) AS message_count
        FROM sujet_forum AS s
        JOIN utilisateur AS u ON s.id_utilisateur = u.id_utilisateur
        LEFT JOIN message_forum AS m ON m.id_sujet = s.id_sujet
        WHERE s.id_attraction = ?
    ";

    $params = [$id_attraction];

    if ($search !== '') {
        $sql .= " AND s.titre LIKE :search ";
        $params[':search'] = "%$search%";
    }

    $sql .= "
        GROUP BY s.id_sujet, s.titre, s.date_creation, s.epingle, s.ferme, u.nom, u.prenom
        ORDER BY s.epingle DESC, s.date_creation DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $sujets = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur lors du chargement du forum.". $e->getMessage());
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return "à l'instant";
    if ($time < 3600) return "il y a " . floor($time / 60) . " min";
    if ($time < 86400) return "il y a " . floor($time / 3600) . " heure(s)";
    if ($time < 2592000) return "il y a " . floor($time / 86400) . " jour(s)";
    return date("d/m/Y à H:i", strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Forum - <?= htmlspecialchars($attraction['nom']) ?> - Wild West Park</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/CSS/style.css?v=<? filemtime('style.css'); ?>">
</head>
<body>
    <?php include('../includes/header.php');?>

<div class="forum-container">

    <div class="forum-header">
        <h1>Forum - <?= htmlspecialchars($attraction['nom']) ?></h1>
        <p>Discussions autour de cette attraction.</p>
    </div>

    <div class="top-actions">
        <a href="/index.php" class="btn btn-secondary btn-custom">
            ← Retour
        </a>
        <a href="forum_new.php?id_attraction=<?= $id_attraction ?>" class="btn btn-primary btn-custom">
            + Nouveau sujet
        </a>
    </div>

    <form method="GET" class="mb-4">
        <input type="hidden" name="id_attraction" value="<?= $id_attraction ?>">
        <input 
            type="text" 
            name="search" 
            class="form-control search-box"
            placeholder="Rechercher un sujet..." 
            value="<?= htmlspecialchars($search) ?>"
        >
    </form>

    <?php if (empty($sujets)): ?>
        <div class="empty-box">Aucun sujet pour cette attraction.</div>
    <?php endif; ?>

    <?php foreach ($sujets as $sujet): ?>
        <a href="forum_thread.php?id_sujet=<?= $sujet['id_sujet'] ?>" class="text-decoration-none">
            <div class="thread-card">
                <div class="thread-title">
                    <?php if ($sujet['epingle']): ?>📌 <?php endif; ?>
                    <?php if ($sujet['ferme']): ?>🔒 <?php endif; ?>
                    <?= htmlspecialchars($sujet['titre']) ?>
                </div>
                <div class="thread-info">
                    Par <?= htmlspecialchars($sujet['prenom'] . ' ' . $sujet['nom']) ?> •
                    <?= timeAgo($sujet['date_creation']) ?> •
                    <?= $sujet['message_count'] ?> message(s)
                </div>
            </div>
        </a>
    <?php endforeach; ?>

</div>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php') ?>
</body>
</html>