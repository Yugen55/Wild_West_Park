<?php 

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

$heure_ouverture = $attraction['heure_ouverture'];
$heure_fermeture = $attraction['heure_fermeture'];
$duree_minutes   = $attraction['duree_minutes'];
$places_restantes = $attraction['places_disponibles'];

$aujourdhui = new DateTime();

$jour_debut_semaine = clone $aujourdhui;
$jour_debut_semaine->modify('+' . ($semaine * 7) . ' days');

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
    "nom" => $jour_nom[$num],
    "date" => $jour->format('Y-m-d'),
    ];
}


foreach ($jours_semaine as $jour){
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM creneau WHERE id_attraction = ? AND date_creneau = ?");
    $stmt->execute([$id_attraction_actuel, $jour['date']]);
    $nombre_creneaux = $stmt->fetchColumn();
    if($nombre_creneaux == 0){
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

        foreach($creneaux_horaires as $heure){
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
    <title>Reservation Wild West Park</title>
    <?php 
    include('includes/header.php');

    ?>
</head>
<body>


?>


</body>
</html>


DÉBUT

// --- 1. Récupération des paramètres ---
Récupérer id_utilisateur depuis la session (ou null)
Récupérer id_attraction_actuel depuis POST ou GET (ou null)
Récupérer le paramètre "semaine" depuis GET (ex: ?semaine=0 pour la semaine actuelle, 1 pour la suivante, etc.)
    SI semaine n'est pas défini OU semaine < 0 → semaine = 0

// --- 2. Connexion BDD ---
Se connecter à la base de données (PDO)

// --- 3. Récupération des infos de l'attraction ---
Faire un SELECT sur la table attraction avec id_attraction_actuel
Récupérer : heure_ouverture, heure_fermeture, duree_minutes, capacite_max

SI l'attraction n'existe pas → afficher une erreur et arrêter le script

// --- 4. Calcul de la date de début et de fin de la semaine demandée ---
Calculer la date du "lundi" de la semaine actuelle (avec DateTime, en tenant compte de la date du jour + décalage de "semaine" x 7 jours)
Calculer la date du "dimanche" (ou vendredi, selon tes jours d'ouverture) de cette même semaine

// --- 5. Construire la liste des jours de la semaine à afficher ---
Créer un tableau jours_semaine vide
POUR i allant de 0 à 6 (7 jours) :
    Calculer la date du jour i (lundi + i jours)
    Ajouter dans jours_semaine : [
        "nom" => nom du jour en français (Lundi, Mardi, ...),
        "date" => date au format Y-m-d
    ]
FIN POUR

// --- 6. Génération automatique des créneaux manquants pour cette semaine ---
POUR CHAQUE jour DANS jours_semaine :
    Vérifier si des créneaux existent déjà en base pour (id_attraction_actuel + date du jour)
        → SELECT COUNT(*) FROM creneau WHERE id_attraction = ? AND date_creneau = ?

    SI aucun créneau n'existe pour ce jour :
        Calculer la liste des créneaux horaires à partir de heure_ouverture, heure_fermeture, duree_minutes
        (boucle avec DateTime + DateInterval, comme évoqué)

        POUR CHAQUE créneau_horaire calculé :
            Calculer heure_fin = heure_debut + duree_minutes

            INSERT INTO creneau (id_attraction, date_creneau, heure_debut, heure_fin, places_restantes)
            VALUES (id_attraction_actuel, date du jour, heure_debut, heure_fin, capacite_max)
        FIN POUR
    FIN SI
FIN POUR

// --- 7. Récupération des créneaux de la semaine (après génération) ---
SELECT * FROM creneau 
WHERE id_attraction = ? 
AND date_creneau BETWEEN (date du lundi) AND (date du dimanche)
ORDER BY date_creneau, heure_debut

Stocker le résultat dans $creneaux_semaine

// --- 8. Organiser les créneaux par jour pour l'affichage ---
Créer un tableau associatif creneaux_par_jour
POUR CHAQUE créneau DANS creneaux_semaine :
    Ajouter le créneau dans creneaux_par_jour[date_creneau][]
FIN POUR

// --- 9. Transmettre les données à la vue (HTML + JS) ---
Encoder en JSON : jours_semaine, creneaux_par_jour (et places_restantes pour chaque créneau)
Encoder la valeur de "semaine" actuelle (pour générer les liens précédent/suivant)

FIN

