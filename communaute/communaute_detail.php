<?php
require '../session.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db.php';
$pdo = getDB();

$id_utilisateur = $_SESSION['id_utilisateur'] ?? null;

if (!isset($_GET['id'])) {
    http_response_code(404);
    include '../404.php';
    exit;
}

$id_communaute_actuel = (int) $_GET['id'];


$stmt = $pdo->prepare("SELECT * FROM communaute WHERE id_communaute = ?");
$stmt->execute([$id_communaute_actuel]);
$communaute = $stmt->fetch();

if (!$communaute) {
    http_response_code(404);
    include '../error/404.php';
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM membre_communaute WHERE id_communaute = ?");
$stmt->execute([$id_communaute_actuel]);
$nb_inscrit = $stmt->fetchColumn();

$places_restantes = max(0, $communaute['nb_places_max'] - $nb_inscrit);


$deja_inscrit = false;
if ($id_utilisateur !== null) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM membre_communaute WHERE id_communaute = ? AND id_utilisateur = ?");
    $stmt->execute([$id_communaute_actuel, $id_utilisateur]);
    $deja_inscrit = (bool) $stmt->fetchColumn();
}


$laisser_avis = null;
if ($id_utilisateur !== null && $deja_inscrit) {
    $laisser_avis = '<div class="container-button-avis"><a href="/interaction_textuelle/avis_form.php?id_communaute=' . $id_communaute_actuel . '" class="btn btn-warning rounded-pill px-4 py-2 fw-bold">Laisser un avis</a></div>';
}


$pourcentage = 0;
if ($communaute['nb_places_max'] > 0) {
    $pourcentage = min(100, ($nb_inscrit / $communaute['nb_places_max']) * 100);
}


$complet = ($nb_inscrit >= $communaute['nb_places_max']);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($communaute['titre'] ?? 'Communauté') ?> - Wild West Park</title>
</head>
<body>
<?php include('../includes/header.php'); ?>

<div class="Card">
    <div class="ImageContainer">
        <div class="ImageBien">
            <?php if (!empty($communaute['image'])): ?>
                <img src="<?= htmlspecialchars($communaute['image']) ?>" alt="<?= htmlspecialchars($communaute['titre']) ?>">
            <?php else: ?>
                <img src="../images/default_communaute.jpg" alt="<?= htmlspecialchars($communaute['titre']) ?>">
            <?php endif; ?>
        </div>
    </div>

    <div class="Description">
        <div class="location">
            <h2><?= htmlspecialchars($communaute['titre']) ?></h2>
        </div>

        <div class="stats">
            <div class="description-text">
                <h4>Description</h4>
                <p><?= nl2br(htmlspecialchars($communaute['description'])) ?></p>
            </div>

            <?php if (!empty($communaute['date_sortie'])): ?>
            <div class="room">
                <h4>Date de la sortie :</h4>
                <h4 class="txtgris"><?= date('d/m/Y', strtotime($communaute['date_sortie'])) ?></h4>
            </div>
            <?php endif; ?>

            <div class="beach">
                <h4>Places occupées :</h4>
                <h4 class="txtgris"><?= $nb_inscrit ?>/<?= $communaute['nb_places_max'] ?> places</h4>
            </div>
        </div>

        <div class="progress mt-3" style="height: 24px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated"
                 role="progressbar"
                 style="width: <?= htmlspecialchars($pourcentage) ?>%;"
                 aria-valuenow="<?= htmlspecialchars($nb_inscrit) ?>"
                 aria-valuemin="0"
                 aria-valuemax="<?= htmlspecialchars($communaute['nb_places_max']) ?>">
                <?= $nb_inscrit ?>/<?= htmlspecialchars($communaute['nb_places_max']) ?>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-center mt-4" style="flex-wrap: nowrap;">
            <?php if ($id_utilisateur === null): ?>
                <a href="/connexion_inscription/connexionv2.php" class="btn btn-dark rounded-pill px-5 py-3">Rejoindre</a>
            <?php elseif ($deja_inscrit): ?>
                <button type="button" class="btn btn-secondary rounded-pill px-5 py-3" disabled>Déjà inscrit</button>
            <?php elseif ($complet): ?>
                <button type="button" class="btn btn-secondary rounded-pill px-5 py-3" disabled>Complet</button>
            <?php else: ?>
                <form method="POST" action="/communaute/communaute.php">
                    <input type="hidden" name="id_communaute" value="<?= htmlspecialchars($id_communaute_actuel) ?>">
                    <input type="hidden" name="rejoindre" value="1">
                    <button type="submit" class="btn btn-dark rounded-pill px-5 py-3">Rejoindre</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="d-flex gap-2 justify-content-center mt-3" style="flex-wrap:nowrap;">
            <button onclick="copierLien()" class="btn btn-outline-secondary rounded-pill px-4 py-2" style="white-space:nowrap;">
                <i class="bi bi-link-45deg me-1"></i> Copier le lien
            </button>
            <a href="mailto:?subject=<?= urlencode('Rejoins cette communauté : ' . ($communaute['titre'] ?? '')) ?>&body=<?= urlencode('Salut ! Je te propose de rejoindre cette communauté : ' . ($communaute['titre'] ?? '') . ' - ' . (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] . '/communaute_detail.php?id=' . $id_communaute_actuel : '')) ?>"
               class="btn btn-outline-secondary rounded-pill px-4 py-2" style="white-space:nowrap;">
                <i class="bi bi-envelope me-1"></i> Partager par email
            </a>
        </div>
        <div id="copie-msg" class="text-center text-success small mt-2" style="display:none;">✓ Lien copié !</div>
        <?php if($deja_inscrit): ?>
        <a href="/communaute/communaute_chat.php?id=<?= htmlspecialchars($communaute['id_communaute']) ?>" class="btn btn-dark rounded-pill px-5 py-3">Ouvrir le chat</a>
        <?php endif; ?>
    </div>
</div>

<script>
function copierLien() {
    navigator.clipboard.writeText(window.location.href).then(function() {
        document.getElementById('copie-msg').style.display = 'block';
        setTimeout(function() {
            document.getElementById('copie-msg').style.display = 'none';
        }, 2000);
    });
}
</script>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php') ?>
</body>
</html>
