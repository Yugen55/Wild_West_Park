<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);


require 'session.php';
require 'db.php';
$pdo = getDB();

/*if (!isset($_GET['id'])) {
    http_response_code(404);
    include '404.php';
    exit;
}*/

$id_utilisateur = $_SESSION['id_utilisateur'] ?? NULL;

$id_attraction_actuel = (int) $_GET['id'];

$stmt = $pdo->prepare("
    SELECT utilisateur.*, avis.*
    FROM utilisateur
    JOIN avis ON utilisateur.id_utilisateur = avis.id_utilisateur
    WHERE avis.id_attraction = ?
");
$stmt->execute([$id_attraction_actuel]);
$avis = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT *
    FROM attraction
    WHERE id_attraction = ?
");
$stmt->execute([$id_attraction_actuel]);

$attraction = $stmt->fetch();

$laisser_avis = NULL;

if ($id_utilisateur !== NULL) {
    $stmt = $pdo->prepare("
    SELECT reservation.*
    FROM reservation
    JOIN creneau ON reservation.id_creneau = creneau.id_creneau
    WHERE reservation.id_utilisateur = ?
    AND creneau.id_attraction = ?
");
$stmt->execute([$id_utilisateur, $id_attraction_actuel]);
$existe = $stmt->fetchAll();

if (count($existe) > 0) {
    $laisser_avis = '<div class="container-button-avis">
        <a href="/interaction_textuelle/avis_form.php?id=' . $id_attraction_actuel . '"class="btn btn-warning rounded-pill px-4 py-2 fw-bold">Laisser un avis</a>
        </div>';
    }
}
?>

<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Attraction - Wild West Park</title>
    <script src="attraction.js"></script>
  </head>
  <body>

  <?php include('includes/header.php'); ?>

    <div class="Card">

      <div class="ImageContainer">
        <div class="ImageBien">
          <?php if ($attraction): ?>
          <img src="<?= (!empty(htmlspecialchars($attraction['image'])) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/images/' . $attraction['image'])) 
    ? '/images/' . htmlspecialchars($attraction['image']) 
    : '/categorie/a.jpg' ?>" alt="Attraction">
          <?php else: ?>
          <p>Attraction introuvable</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="Description">

        <div class="location">
          <h2><?= htmlspecialchars($attraction['nom']) ?? ''; ?></h2>
        </div>

        <div class="stats">
          <div class="description-text">
            <h4>Description</h4>
            <p><?= htmlspecialchars($attraction['description'] ?? ''); ?></p>
          </div>
          <div class="room">
            <h4>Taille minimum requise :</h4>
            <h4 class="txtgris"><?php echo htmlspecialchars($attraction['taille_minimum'] ?? '') ; ?></h4>
          </div>
          <div class="beach">
            <h4>Age minimum requis :</h4>
            <h4 class="txtgris"><?php echo htmlspecialchars($attraction['age_minimum'] ?? ''); ?></h4>
          </div>
          <div class="surface">
            <h4>Durée de l'attraction :</h4>
            <h4 class="txtgris"><?php echo htmlspecialchars($attraction['duree_minutes'] ?? ''); ?></h4>
          </div>
        </div>

        <div class="cash">
          <h4>Prix du ticket individuel :</h4>
          <h4> <?php echo htmlspecialchars($attraction['prix'] ?? ''); ?> €</h4>
        </div>

        <form method="POST" action="/reservationv3.php">
          <input type="hidden" name="id_attraction" value="<?= htmlspecialchars($id_attraction_actuel) ?>">
          <input type="hidden" name="id_utilisateur" value="<?= htmlspecialchars($id_utilisateur) ?>">
          <button type="submit" class="btn btn-dark rounded-pill px-5 py-3 d-block mx-auto">
            Réserver une visite
          </button>
        </form>

        <div class="d-flex gap-2 justify-content-center mt-2" style="flex-wrap:nowrap;">
          <button onclick="copierLien()" class="btn btn-outline-secondary rounded-pill px-4 py-2" style="white-space:nowrap;">
            <i class="bi bi-link-45deg me-1"></i> Copier le lien
          </button>
          <a href="mailto:?subject=<?= urlencode('Bien immobilier - Altos Prestige') ?>&body=<?= urlencode('Découvrez ce bien sur Altos Prestige : https://altoprestige.fr/attraction.php?id=' . $id_attraction_actuel) ?>" 
             class="btn btn-outline-secondary rounded-pill px-4 py-2" style="white-space:nowrap;">
            <i class="bi bi-envelope me-1"></i> Partager par email
          </a>
        </div>

        <div id="copie-msg" class="text-center text-success small mt-2" style="display:none;">✓ Lien copié !</div>

      </div>

    </div>

    <script>
    function copierLien() {
      navigator.clipboard.writeText(window.location.href).then(function() {
        var msg = document.getElementById('copie-msg');
        msg.style.display = 'block';
        setTimeout(function() { msg.style.display = 'none'; }, 2000);
      });
    }
    </script>

    <div class="espacement">
    <?php echo $laisser_avis ?> 


    <?php foreach ($avis as $a): ?>
    <div class="container-avis">
        <div class="avis-card">
        <div class="avis-header">
            <img src=<?= (!empty($a['photo_profil']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/images/' . $a['photo_profil'])) 
    ? '/images/' . htmlspecialchars($a['photo_profil']) 
    : '/images/parchemin.png' ?> alt="Photo de profil" class="avis-photo">
            <div class="avis-info">
                <h3 class="avis-nom"><?php echo htmlspecialchars($a['prenom'] . " " . $a['nom']) ?></h3>
            </div>
        </div>
        <p class="avis-texte">
            <?php echo htmlspecialchars($a['message'])?>
        </p>
        <span class="avis-date"><?php echo htmlspecialchars($a['date_avis'])?></span>
        </div>
    </div>
    <?php endforeach; ?>

    <a href="discussion/forum.php?id_attraction=<?= htmlspecialchars($id_attraction_actuel) ?>" 
   class="btn btn-primary px-4 py-2 fw-bold rounded-pill">
    Voir le forum
    </a>

    </div>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php') ?>
  </body>
</html>