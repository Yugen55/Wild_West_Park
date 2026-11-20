
<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../session.php';
require '../db.php';
$pdo = getDB();

$id_utilisateur = $_SESSION['id_utilisateur'] ?? NULL;

$stmt = $pdo->prepare("
    SELECT id_concours, titre, description, date_debut, date_fin, jetons_recompense, statut
    FROM concours_photo
    ORDER BY
        CASE statut
            WHEN 'en_cours' THEN 0
            WHEN 'a_venir' THEN 1
            ELSE 2
        END,
        date_debut DESC
");
$stmt->execute();
$tous_concours = $stmt->fetchAll();

$concours_liste = [];

foreach ($tous_concours as $concours) {
    $id_concours = (int) $concours['id_concours'];

    $vote_utilisateur = null;
    $deja_participe = false;

    if ($id_utilisateur !== null) {
        $stmt = $pdo->prepare("
            SELECT id_participation
            FROM vote_photo
            WHERE id_concours = ? AND id_utilisateur = ?
        ");
        $stmt->execute([$id_concours, $id_utilisateur]);
        $ligne = $stmt->fetch();
        if ($ligne) {
            $vote_utilisateur = (int) $ligne['id_participation'];
        }

        $stmt = $pdo->prepare("
            SELECT id_participation
            FROM participation_concours
            WHERE id_concours = ? AND id_utilisateur = ?
        ");
        $stmt->execute([$id_concours, $id_utilisateur]);
        $deja_participe = (bool) $stmt->fetch();
    }

    $stmt = $pdo->prepare("
        SELECT
            p.id_participation,
            p.photo,
            p.id_utilisateur,
            u.prenom,
            u.nom,
            COUNT(v.id_utilisateur) AS total_votes
        FROM participation_concours p
        JOIN utilisateur u ON u.id_utilisateur = p.id_utilisateur
        LEFT JOIN vote_photo v
            ON v.id_participation = p.id_participation
            AND v.id_concours = ?
        WHERE p.id_concours = ?
        GROUP BY p.id_participation, p.photo, p.id_utilisateur, u.prenom, u.nom
        ORDER BY total_votes DESC, p.id_participation ASC
    ");
    $stmt->execute([$id_concours, $id_concours]);
    $photos_brutes = $stmt->fetchAll();

    $total_global = 0;
    $max_votes = 0;
    foreach ($photos_brutes as $photo) {
        $total_global += (int) $photo['total_votes'];
        $max_votes = max($max_votes, (int) $photo['total_votes']);
    }

    $photos = [];
    foreach ($photos_brutes as $photo) {
        $nb_votes = (int) $photo['total_votes'];
        $photos[] = [
            'id_participation' => (int) $photo['id_participation'],
            'photo'            => $photo['photo'],
            'auteur'           => trim($photo['prenom'] . ' ' . $photo['nom']),
            'total_votes'      => $nb_votes,
            'pourcentage'      => $total_global > 0 ? round(($nb_votes / $total_global) * 100) : 0,
            'a_vote_pour'      => ($vote_utilisateur === (int) $photo['id_participation']),
            'est_gagnante'     => ($concours['statut'] === 'termine' && $max_votes > 0 && $nb_votes === $max_votes),
        ];
    }

    $concours_liste[] = [
        'id_concours'      => $id_concours,
        'titre'            => $concours['titre'],
        'description'      => $concours['description'],
        'date_debut'       => $concours['date_debut'],
        'date_fin'         => $concours['date_fin'],
        'jetons_recompense'=> (int) $concours['jetons_recompense'],
        'statut'           => $concours['statut'],
        'photos'           => $photos,
        'total_global'     => $total_global,
        'deja_participe'   => $deja_participe,
    ];
}

$labels_statut = [
    'a_venir'  => 'À venir',
    'en_cours' => 'En cours',
    'termine'  => 'Terminé',
];
?>

<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Concours photo - Wild West Park</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  </head>
  <body>

  <?php include('../includes/header.php'); ?>

    <div class="entete-page">
      <h2>Concours photo</h2>

      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalConcours">
        Créer un concours
      </button>
    </div>

    <?php if (empty($concours_liste)): ?>
      <p class="text-center text-muted mt-4">Aucun concours pour le moment.</p>
    <?php else: ?>

    <div class="grille-concours">
      <?php foreach ($concours_liste as $c):
          $id = $c['id_concours'];
      ?>
      <div class="carte-concours statut-<?= $c['statut'] ?>"
           data-bs-toggle="modal" data-bs-target="#modalAjout<?= $id ?>">

        <div class="carte-entete">
          <h3><?= htmlspecialchars($c['titre']) ?></h3>
          <span class="badge-statut <?= $c['statut'] ?>"><?= $labels_statut[$c['statut']] ?? htmlspecialchars($c['statut']) ?></span>
        </div>

        <p class="carte-description"><?= htmlspecialchars($c['description']) ?></p>

        <div class="carte-meta">
          <span>Du <?= (new DateTime($c['date_debut']))->format('d/m/Y') ?> au <?= (new DateTime($c['date_fin']))->format('d/m/Y') ?></span>
          <span>🪙 <?= $c['jetons_recompense'] ?></span>
        </div>

        <div class="carte-carrousel">
          <?php if (!empty($c['photos'])): ?>
          <div id="carrousel<?= $id ?>" class="carousel slide" data-bs-ride="false">
            <div class="carousel-inner">
              <?php foreach ($c['photos'] as $i => $p):
                  $chemin_photo = (!empty($p['photo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/images/' . $p['photo']))
                      ? '/images/' . htmlspecialchars($p['photo'])
                      : '/categorie/a.jpg';
              ?>
              <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                <img src="<?= $chemin_photo ?>" alt="Photo de <?= htmlspecialchars($p['auteur']) ?>">
                <div class="carousel-caption">
                  <div class="nom-auteur">
                    <?= htmlspecialchars($p['auteur']) ?>
                    <?php if ($p['est_gagnante']): ?><span class="badge-gagnante">🏆 Gagnante</span><?php endif; ?>
                  </div>
                  <div class="votes-info"><?= $p['total_votes'] ?> vote(s) — <?= $p['pourcentage'] ?>%</div>

                  <?php if ($id_utilisateur !== null && $c['statut'] === 'en_cours'): ?>
                  <form method="POST" action="/concours_photo/vote.php" onclick="event.stopPropagation()">
                    <input type="hidden" name="id_concours" value="<?= $id ?>">
                    <input type="hidden" name="id_participation" value="<?= $p['id_participation'] ?>">
                    <button type="submit" class="btn-vote-carrousel" <?= $p['a_vote_pour'] ? 'disabled' : '' ?>>
                      <?= $p['a_vote_pour'] ? '✓ Votre vote' : 'Voter pour cette photo' ?>
                    </button>
                  </form>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>

            <?php if (count($c['photos']) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#carrousel<?= $id ?>" data-bs-slide="prev" onclick="event.stopPropagation()">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Précédent</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carrousel<?= $id ?>" data-bs-slide="next" onclick="event.stopPropagation()">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Suivant</span>
            </button>
            <div class="carousel-indicators" onclick="event.stopPropagation()">
              <?php foreach ($c['photos'] as $i => $p): ?>
              <button type="button" data-bs-target="#carrousel<?= $id ?>" data-bs-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-label="Photo <?= $i + 1 ?>"></button>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
          <?php else: ?>
          <div class="carte-vide">Aucune photo n'a encore été soumise pour ce concours.</div>
          <?php endif; ?>
        </div>

        <div class="carte-pied">
          <span><?= $c['total_global'] ?> vote<?= $c['total_global'] > 1 ? 's' : '' ?> au total</span>
          <?php if ($id_utilisateur !== null): ?>
            <?php if ($c['deja_participe']): ?>
              <span class="text-success fw-bold">✓ Vous participez</span>
            <?php elseif ($c['statut'] === 'en_cours'): ?>
              <span class="lien-ajout">+ Ajouter ma photo</span>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="modal fade" id="modalAjout<?= $id ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Participer à « <?= htmlspecialchars($c['titre']) ?> »</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <?php if ($id_utilisateur === null): ?>
                <p class="text-muted">Connectez-vous pour participer à ce concours.</p>
              <?php elseif ($c['statut'] !== 'en_cours'): ?>
                <p class="text-muted">Ce concours n'accepte pas de nouvelles participations pour le moment.</p>
              <?php elseif ($c['deja_participe']): ?>
                <p class="text-success fw-bold">✓ Vous participez déjà à ce concours.</p>
              <?php else: ?>
                <form method="POST" action="/concours_photo/participation_ajout.php" enctype="multipart/form-data">
                  <input type="hidden" name="id_concours" value="<?= $id ?>">
                  <div class="mb-3">
                    <label for="photo<?= $id ?>" class="form-label">Choisissez votre photo :</label>
                    <input type="file" class="form-control" id="photo<?= $id ?>" name="photo" accept="image/*" required>
                  </div>
                  <button type="submit" name="ok" class="btn btn-warning">Envoyer ma photo</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <?php endforeach; ?>
    </div>

    <?php endif; ?>

    <div class="modal fade" id="modalConcours" tabindex="-1" aria-labelledby="modalConcoursLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="POST" action="/concours_photo/creer_concours.php" enctype="multipart/form-data">
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="modalConcoursLabel">Créer un concours photo</h1>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="place mb-3">
                <label for="titre" class="form-label">Titre :</label>
                <input type="text" class="form-control" id="titre" name="titre"
                       placeholder="Le plus beau coucher de soleil" required>
              </div>
              <div class="place mb-3">
                <label for="description" class="form-label">Description :</label>
                <textarea class="form-control" id="description" name="description"
                          placeholder="Décrivez le thème du concours" rows="3"></textarea>
              </div>
              <div class="place mb-3">
                <label for="date_debut" class="form-label">Date de début :</label>
                <input type="date" class="form-control" id="date_debut" name="date_debut" required>
              </div>
              <div class="place mb-3">
                <label for="date_fin" class="form-label">Date de fin :</label>
                <input type="date" class="form-control" id="date_fin" name="date_fin" required>
              </div>
              <div class="place">
                <label for="jetons_recompense" class="form-label">Jetons de récompense :</label>
                <input type="number" class="form-control" id="jetons_recompense" name="jetons_recompense"
                       value="0" min="0">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
              <button type="submit" name="ok" class="btn btn-primary">Créer le concours</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php') ?>
  </body>
</html>