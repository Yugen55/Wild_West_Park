<?php 

require 'db.php';
$pdo = Getdb();

$id_utilisateur = $_POST['id_utilisateur'];
$id_attraction_actuel = $_POST['id_attraction'];

$stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
$stmt->execute([$id_utilisateur]);
$attraction = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM attraction WHERE id_attraction = ?");
$stmt->execute([$id_attraction_actuel]);
$attraction = $stmt->fetch(PDO::FETCH_ASSOC);

$heure_ouverture = $attraction['heure_ouverture'];
$heure_fermeture = $attraction['heure_fermeture'];
$duree_minutes   = $attraction['duree_minutes'];

$creneaux_horaires = [];
$debut = new DateTime($heure_ouverture);
$fin   = new DateTime($heure_fermeture);

while ($debut < $fin) {
    $creneaux_horaires[] = $debut->format('H:i');
    $debut->add(new DateInterval('PT' . $duree_minutes . 'M'));
}

if (isset($_GET['semaine'])) {
    $semaine = (int) $_GET['semaine'];
} else {
    $semaine = 0;
}

$aujourdhui = new DateTime();

$lundi_semaine_actuelle = clone $aujourdhui;
$lundi_semaine_actuelle->modify('+' . $semaine . ' weeks');

$jours_semaine = [];
for ($i = 0; $i < 7; $i++) {
    $jour = clone $lundi_semaine_actuelle;
    $jour->modify('+' . $i . ' days');
    $jours_semaine[] = $jour;
}

if ($semaine < 0) {
    $semaine = 0;
}

$jour_nom = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];
$numero_jour = [];


?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Wild West Park</title>
    <?php 
    include('includes/header.php');

    ?>
</head>
<body>
<?php 
echo "<form method='post' action='back_reservation.php' class='reservation'>";
echo "<input type='hidden' name='id_attraction' value='$id_attraction_actuel'>";
echo "<input type='hidden' name='id_utilisateur' value='$id_utilisateur'>";
echo "<input type='hidden' name='duree_minutes' value='$duree_minutes'>";

foreach ($jours_semaine as $jour) {
    $num = (int) $jour->format('N');
    $date_creneau = $jour->format('Y-m-d');
    echo "<div class='reservation'>";
    echo "<h3>" . $jour_nom[$num] . " " . $jour->format('d/m/Y') . "</h3>";
    echo "<div class='creneaux-jour'>";
    foreach ($creneaux_horaires as $heure) {
        $valeur = $date_creneau . ' ' . $heure;
        echo "<button type='submit' name='heure' value='$valeur' class='btn btn-dark'>$heure</button>";
    }
    echo "</div>";
}

echo "</form>";

?>


</body>
</html>