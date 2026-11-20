<?php 


ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'session.php';

if (isset($_SESSION['id_utilisateur'])) {
    $id_utilisateur = $_SESSION['id_utilisateur'];
} else {
    $id_utilisateur = null;
}

if (isset($_POST['id_attraction'])) {
    $id_attraction_actuel = (int) $_POST['id_attraction'];
} elseif (isset($_GET['id'])) {
    $id_attraction_actuel = (int) $_GET['id'];
} else {
    $id_attraction_actuel = null;
}

if (isset($_GET['semaine'])) {
    $semaine = (int) $_GET['semaine'];
} else {
    $semaine = 0;
}

if ($semaine < 0) {
    $semaine = 0;
}


require 'db.php';
$pdo = getDB();

$stmt = $pdo->prepare("SELECT heure_ouverture, heure_fermeture, duree_minutes, places_disponibles FROM attraction WHERE id_attraction = ?");
$stmt->execute([$id_attraction_actuel]);
$attraction = $stmt->fetch(PDO::FETCH_ASSOC);

$heure_ouverture   = $attraction['heure_ouverture'];
$heure_fermeture   = $attraction['heure_fermeture'];
$duree_minutes     = $attraction['duree_minutes'];
$places_restantes  = $attraction['places_disponibles'];

$aujourdhui = new DateTime();
$aujourdhui->setTime(0, 0, 0);

$lundi_semaine_actuelle = clone $aujourdhui;
$lundi_semaine_actuelle->modify('monday this week');
$lundi_semaine_actuelle->modify('+' . $semaine . ' weeks');

$dimanche_semaine_actuelle = clone $lundi_semaine_actuelle;
$dimanche_semaine_actuelle->modify('+6 days');

$jour_nom = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];

$jours_semaine = [];
for ($i = 0; $i < 7; $i++) {
    $jour = clone $lundi_semaine_actuelle;
    $jour->modify('+' . $i . ' days');
    $num = (int) $jour->format('N');
    $jours_semaine[] = [
        "nom"    => $jour_nom[$num],
        "date"   => $jour->format('Y-m-d'),
        "passe"  => $jour < $aujourdhui,
    ];
}

foreach ($jours_semaine as $jour) {
    if ($jour['passe']) {
        continue;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM creneau WHERE id_attraction = ? AND date_creneau = ?");
    $stmt->execute([$id_attraction_actuel, $jour['date']]);
    $nombre_creneaux = $stmt->fetchColumn();

    if ($nombre_creneaux == 0) {
        $creneaux_horaires = [];
        $debut = new DateTime($heure_ouverture);
        $fin   = new DateTime($heure_fermeture);

        while ($debut < $fin) {
            $heure_debut = clone $debut;
            $debut->add(new DateInterval('PT' . $duree_minutes . 'M'));
            $heure_fin = clone $debut;

            $creneaux_horaires[] = [
                'debut' => $heure_debut->format('H:i:s'),
                'fin'   => $heure_fin->format('H:i:s'),
            ];
        }

        foreach ($creneaux_horaires as $heure) {
            $stmt = $pdo->prepare("INSERT INTO creneau (id_attraction, date_creneau, heure_debut, heure_fin, places_restantes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_attraction_actuel, $jour['date'], $heure['debut'], $heure['fin'], $places_restantes]);
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM creneau WHERE id_attraction = ? AND date_creneau BETWEEN ? AND ? ORDER BY date_creneau, heure_debut");
$stmt->execute([
    $id_attraction_actuel,
    $lundi_semaine_actuelle->format('Y-m-d'),
    $dimanche_semaine_actuelle->format('Y-m-d')
]);
$creneaux_semaine = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation - Wild West Park</title>
    <?php include('includes/header.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .jour-passe {
            opacity: 0.5;
            pointer-events: none;
        }
        .jour-passe .btn {
            background-color: #ccc !important;
            border-color: #ccc !important;
        }
    </style>
</head>
<body>

<div class="navigation-semaine">
    <?php if ($semaine > 0): ?>
        <a href="?id=<?= htmlspecialchars($id_attraction_actuel) ?>&semaine=<?= $semaine - 1 ?>" class="btn btn-outline-dark">← Semaine précédente</a>
    <?php endif; ?>

    <span>Du <?= htmlspecialchars($lundi_semaine_actuelle->format('d/m/Y')) ?> au <?= htmlspecialchars($dimanche_semaine_actuelle->format('d/m/Y')) ?></span>

    <a href="?id=<?= htmlspecialchars($id_attraction_actuel) ?>&semaine=<?= htmlspecialchars($semaine + 1) ?>" class="btn btn-outline-dark">Semaine suivante →</a>
</div>

<?php foreach ($jours_semaine as $jour): ?>
    <div class="reservation <?= htmlspecialchars($jour['passe']) ? 'jour-passe' : '' ?>">
        <h3><?= htmlspecialchars($jour['nom']) . " " . htmlspecialchars((new DateTime($jour['date']))->format('d/m/Y')) ?></h3>
        <div class="creneaux-jour">
            <?php if ($jour['passe']): ?>
                <span class="text-muted">Jour passé</span>
            <?php else: ?>
                <?php foreach ($creneaux_semaine as $creneau): ?>
                    <?php if ($creneau['date_creneau'] === $jour['date']): ?>
                        <?php
                            $heure_affichee = substr($creneau['heure_debut'], 0, 5);
                            $valeur = $creneau['date_creneau'] . ' ' . $creneau['heure_debut'];
                        ?>
                        <?php if ($creneau['places_restantes'] > 0): ?>
                            <button type="button"
                                    class="btn btn-dark btn-creneau"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalReservation"
                                    data-valeur="<?= htmlspecialchars($valeur) ?>"
                                    data-places="<?= (int) htmlspecialchars($creneau['places_restantes']) ?>"
                                    data-heure="<?= htmlspecialchars($heure_affichee) ?>"
                                    data-jour="<?= htmlspecialchars($jour['nom']) ?>">
                                <?= htmlspecialchars($heure_affichee) ?>
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary" disabled>
                                <?= htmlspecialchars($heure_affichee) ?> (complet)
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<div class="modal fade" id="modalReservation" tabindex="-1" aria-labelledby="modalReservationLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalReservationLabel">Réserver ce créneau</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <p id="modal-recap-creneau"></p>
        <label for="nb-personnes" class="form-label">Nombre de personnes</label>
        <input type="number" id="nb-personnes" class="form-control" min="1" value="1">
        <p class="text-danger mt-2 d-none" id="modal-erreur"></p>
        <p class="text-success mt-2 d-none" id="modal-succes"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="button" class="btn btn-dark" id="btn-confirmer-reservation">Confirmer</button>
      </div>
    </div>
  </div>
</div>

<script>
let placesDisponiblesCourant = 0;
let valeurCreneauCourant = "";
let boutonCreneauCourant = null;

const modalElement = document.getElementById('modalReservation');
const modalReservation = new bootstrap.Modal(modalElement);

document.querySelectorAll('.btn-creneau').forEach(function (bouton) {
    bouton.addEventListener('click', function () {
        boutonCreneauCourant = this;
        valeurCreneauCourant = this.dataset.valeur;
        placesDisponiblesCourant = parseInt(this.dataset.places, 10);

        const jour  = this.dataset.jour;
        const heure = this.dataset.heure;

        document.getElementById('modal-recap-creneau').textContent =
            jour + " à " + heure + " — " + placesDisponiblesCourant + " place(s) disponible(s)";

        const inputNb = document.getElementById('nb-personnes');
        inputNb.value = 1;
        inputNb.max = placesDisponiblesCourant;

        document.getElementById('modal-erreur').classList.add('d-none');
        const succes = document.getElementById('modal-succes');
        if (succes) succes.classList.add('d-none');

        modalReservation.show();
    });
});

document.getElementById('btn-confirmer-reservation').addEventListener('click', function () {
    const nb = parseInt(document.getElementById('nb-personnes').value, 10);
    const erreur = document.getElementById('modal-erreur');
    const succes = document.getElementById('modal-succes');

    if (isNaN(nb) || nb < 1 || nb > placesDisponiblesCourant) {
        erreur.textContent = "Nombre de personnes invalide.";
        erreur.classList.remove('d-none');
        return;
    }

    erreur.classList.add('d-none');

    fetch('maj_reservation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id_attraction: <?= (int) $id_attraction_actuel ?>,
            heure: valeurCreneauCourant,
            nb_personnes: nb
        })
    })
    .then(function (reponse) {
        return reponse.json();
    })
    .then(function (donnees) {
        if (!donnees.succes) {
            erreur.textContent = donnees.message || "Erreur lors de la réservation.";
            erreur.classList.remove('d-none');
            return;
        }

        if (succes) {
            succes.textContent = "Réservation confirmée !";
            succes.classList.remove('d-none');
        }

        if (boutonCreneauCourant) {
            boutonCreneauCourant.dataset.places = donnees.places_restantes;

            if (donnees.places_restantes <= 0) {
                boutonCreneauCourant.disabled = true;
                boutonCreneauCourant.classList.remove('btn-dark');
                boutonCreneauCourant.classList.add('btn-secondary');
                boutonCreneauCourant.textContent += " (complet)";
            }
        }

        setTimeout(function () {
            modalReservation.hide();
        }, 1200);
    })
    .catch(function (err) {
        console.error(err);
        erreur.textContent = "Erreur réseau, réessayez.";
        erreur.classList.remove('d-none');
    });
});
modalElement.addEventListener('hidden.bs.modal', function () {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    if (backdrops.length > 0 && !document.querySelector('.modal.show')) {
        backdrops.forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
        document.body.style.removeProperty('overflow');
    }
});
</script>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php') ?>
</body>
</html>