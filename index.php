<?php 
require 'db.php';
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
$gagnant = null;


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
        $nb_votes = (int) $photo['total_votes'];
        $total_global += $nb_votes;
        if ($nb_votes > $max_votes) {
            $max_votes = $nb_votes;
        }
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


    if ($gagnant === null && $concours['statut'] === 'termine' && !empty($photos_brutes)) {
        $premiere_photo = $photos_brutes[0];
        $gagnant = [
            'photo'  => $premiere_photo['photo'],
            'auteur' => trim($premiere_photo['prenom'] . ' ' . $premiere_photo['nom']),
            'votes'  => (int) $premiere_photo['total_votes'],
        ];
    }


    $concours_liste[] = [
        'id_concours'       => $id_concours,
        'titre'             => $concours['titre'],
        'description'       => $concours['description'],
        'date_debut'        => $concours['date_debut'],
        'date_fin'          => $concours['date_fin'],
        'jetons_recompense' => $concours['jetons_recompense'],
        'statut'            => $concours['statut'],
        'photos'            => $photos,
        'vote_utilisateur'  => $vote_utilisateur,
        'deja_participe'    => $deja_participe,
    ];
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Wild West Park</title>
</head>
<body>
    <?php
include 'includes/header.php'; ?>
    <div class="img_accueil">
        <img src="images/Illustration_Wild_West.jpg" style="width:100%; height:50em;" />
    </div>
    <div style="padding:1em; width:100%;" class="section-carte">
        <p id="titre">Découvrez le parc Wild Western :</p>
            <div class="swiper">
                <div class="swiper-wrapper">
                    <div class="swiper-slide"><img src="images/attraction_western1.webp"></div>
                    <div class="swiper-slide"><img src="images/attraction_western2.webp"></div>
                    <div class="swiper-slide"><img src="images/western_attraction4.jpg"></div>
                    <div class="swiper-slide"><img src="images/western_attraction4.webp"></div>
                    <div class="swiper-slide"><img src="images/western_attraction6.webp"></div>
                    <div class="swiper-slide"><img src="images/western_attraction7.webp"></div>
                    <div class="swiper-slide"><img src="images/western_attraction8.webp"></div>
                </div>
            </div>
    </div>
    <div>
        <?php if ($gagnant !== null): ?>    
        <p id="titre">Le gagnant du concours photo est <?= htmlspecialchars($gagnant['auteur']) ?>,<br> il a gagné avec <?= htmlspecialchars($gagnant['votes']) ?> votes</p>
        <div class="conteneur-image">
        <img src="images/<?= htmlspecialchars($gagnant['photo']) ?>" alt="Photo gagnante !" width="700" height="auto">
        <?php endif; ?>
        </div>
        <p id="titre">Le plan du parc, tracer votre chemin ou dessinez pour le fun !</p>
        <div class="conteneur-image">
            <canvas id="canvas-image" width="700" height="700" style="cursor: crosshair;"></canvas>
        </div>
        <button id="btn-effacer" class="btn btn-dark rounded-pill px-5 py-3 d-block mx-auto">Effacer</button>
    </div>
    <script src=dessinv2.js></script>
    <script>
        const swiper = new Swiper('.swiper', {
        loop: true,
        slidesPerView: 3,
        spaceBetween: 20,
        centeredSlides: true,
        spaceBetween: 20,

        autoplay: {
        delay: 3000,
        disableOnInteraction: false,
        },

    breakpoints: {
        768: {
        slidesPerView: 2
        },
        480: {
        slidesPerView: 1
        }
        }
    });
</script>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php') ?>

</body>
</html>