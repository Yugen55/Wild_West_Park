<?php 

require '../db.php';
$pdo = getDB();
require '../session.php';

$id_utilisateur = $_SESSION['id_utilisateur'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rejoindre'])) {

    if (!$id_utilisateur) {
        header('Location: /connexion_inscription/connexionv2.php');
        exit;
    }

    $id_communaute_rejoindre = (int) $_POST['id_communaute'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM membre_communaute WHERE id_communaute = ? AND id_utilisateur = ?");
    $stmt->execute([$id_communaute_rejoindre, $id_utilisateur]);
    $deja_inscrit = $stmt->fetchColumn();

    if (!$deja_inscrit) {
        $stmt = $pdo->prepare("SELECT nb_places_max FROM communaute WHERE id_communaute = ?");
        $stmt->execute([$id_communaute_rejoindre]);
        $nb_places_max = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM membre_communaute WHERE id_communaute = ?");
        $stmt->execute([$id_communaute_rejoindre]);
        $nb_inscrit_actuel = $stmt->fetchColumn();

        if ($nb_inscrit_actuel < $nb_places_max) {
            $stmt = $pdo->prepare("INSERT INTO membre_communaute (id_communaute, id_utilisateur, role_membre, date_adhesion) VALUES (?, ?, 'membre', CURRENT_TIMESTAMP)");
            $stmt->execute([$id_communaute_rejoindre, $id_utilisateur]);
        }
    }

    header('Location: ' . basename($_SERVER['PHP_SELF']));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM communaute");
$stmt->execute();
$communautes = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($communautes as &$c) {
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM membre_communaute WHERE id_communaute = ?");
    $stmt2->execute([$c['id_communaute']]);
    $c['nb_inscrit'] = $stmt2->fetchColumn();
    $c['places_restantes'] = max(0, $c['nb_places_max'] - $c['nb_inscrit']);

    $c['deja_inscrit'] = false;
    if ($id_utilisateur) {
        $stmt3 = $pdo->prepare("SELECT COUNT(*) FROM membre_communaute WHERE id_communaute = ? AND id_utilisateur = ?");
        $stmt3->execute([$c['id_communaute'], $id_utilisateur]);
        $c['deja_inscrit'] = (bool) $stmt3->fetchColumn();
    }
}

$c['places_restantes'] = max(0, $c['nb_places_max'] - $c['nb_inscrit']);

unset($c);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communautés - Wild West Park</title>
    <link rel="stylesheet" href="../CSS/communaute.css">
</head>
<body>
    <?php include('../includes/header.php'); ?>
    <a href="communaute_creer.php" type = "button" class="creer btn btn-success">Créer une sortie</a>
    <div class="container-communaute">
        <?php foreach ($communautes as $c): ?>
            <a href="/communaute_detail.php?id=<?= $c['id_communaute'] ?>">
            <?php $pourcentage = ($c['nb_places_max'] > 0) && ($c['nb_inscrit'] > 0) ? (($c['nb_inscrit'] / $c['nb_places_max']) * 100) : 0; ?>

            <div class="rect-communaute">
    <a href="../communaute/communaute_detail.php?id=<?= $c['id_communaute'] ?>" class="communaute-card-link">
        <div class="communaute-card">
            <div class="communaute-card-image">
                <img src="..." alt="photo sortie">
            </div>
            <div class="communaute-infos">
                <h2><?= htmlspecialchars($c['titre']) ?></h2>
                <p class="date-sortie">Date de la sortie : <?= htmlspecialchars($c['date_sortie']) ?></p>
                <p class="description"><?= htmlspecialchars($c['description']) ?></p>
                <h4><?= $c['nb_inscrit'] ?>/<?= $c['nb_places_max'] ?> places</h4>
            </div>
        </div>
    </a>

    <div class="rejoindre-communaute">
        <?php if (!$id_utilisateur): ?>
            <a href="/connexion_inscription/connexionv2.php" class="btn btn-info">Rejoindre</a>
        <?php elseif ($c['deja_inscrit']): ?>
            <button class="btn btn-secondary" disabled>Déjà inscrit</button>
        <?php elseif ($c['places_restantes'] <= 0): ?>
            <button class="btn btn-secondary" disabled>Complet</button>
        <?php else: ?>
            <form method="post" action="">
                <input type="hidden" name="id_communaute" value="<?= $c['id_communaute'] ?>">
                <button type="submit" name="rejoindre" class="btn btn-info">Rejoindre</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="progress" role="progressbar" aria-valuenow="<?= htmlspecialchars($pourcentage) ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar" style="width: <?= htmlspecialchars($pourcentage) ?>%"></div>
    </div>
</div>
        <?php endforeach; ?>
    </div>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php') ?>
</body>
</html>