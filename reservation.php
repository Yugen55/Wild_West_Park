<?php 

ini_set('display_errors', 1);
error_reporting(E_ALL);

if(isset($_SESSION['id_utilisateur'])){
  $id_utilisateur = $_SESSION['id_utilisateur'];
} else {
  $id_utilisateur = null;
}

if(isset($_POST['id_bien'])){
  $id_attraction_actuel = (int) $_POST['id_attraction'];
} elseif(isset($_GET['id'])){
  $id_attraction_actuel = (int) $_GET['id'];
} else {
  $id_attraction_actuel = null;
}



require 'db.php';
$pdo = getDB();


$stmt = $pdo->prepare("SELECT * FROM attraction WHERE id_attraction = ?");
$stmt->execute([$id_attraction_actuel]);
$attraction = $stmt->fetchAll(PDO::FETCH_ASSOC);

$heure_ouvertue = $attraction['heure_ouverture'];
$heure_fermeture = $attraction['heure_fermeture'];
$duree_minutes = $attraction['duree_minutes'];

$stmt = $pdo->prepare("SELECT date_visite FROM Visiter WHERE id_bien = ?");
$stmt->execute([$id_attraction_actuel]);
$creneau = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <link rel="stylesheet" href="/style.css" />
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Resrevation - Wild West Park</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    let id_utilisateur = <?= $id_utilisateur ?? 'null' ?>;
    let id_attraction_actuel = <?= $id_attraction_actuel ?? 'null' ?>;
    let creneaux_reserves = <?= json_encode($creneau) ?>;
</script>
    <script src="reservation.js"></script>
    <title>Reservation Wild West Park</title>
</head>
<body class="board">
    <?php 
    include('../includes/header.php'); ?>
    <div class="titre">
        <h1 class="crenau">Réservez le créneau de votre choix :</h1>
        <button type="button" class="d-none confirmer taille btn btn-info rounded-pill">
            Confirmer
        </button>
        <div id="message" class="mt-2"></div>

    </div>
    <div class="Container">
        <div class="today">
            <div class="day">
                <h2>Lundi</h2>
            </div>
            <div class="slot">
                <div class="hour"></div>
            </div>
        </div>
        <div class="today">
            <div class="day">
                <h2>Mardi</h2>
            </div>
            <div class="slot">
                <div class="hour"></div>
            </div>
        </div>
        <div class="today">
            <div class="day">
                <h2>Mercredi</h2>
            </div>
            <div class="slot">
                <div class="hour"></div>
            </div>
        </div>
        <div class="today">
        <div class="day">
            <h2>Jeudi</h2>
        </div>
        <div class="slot">
            <div class="hour"></div>
        </div>
        </div>
        <div class="today">
        <div class="day">
            <h2>Vendredi</h2>
        </div>
        <div class="slot">
            <div class="hour"></div>
        </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>
   <script src="../color.js"></script>
</body>
</html>
