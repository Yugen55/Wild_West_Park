<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db.php';
$pdo = getDB();

/*if (!isset($_GET['id'])) {
    http_response_code(404);
    include '404.php';
    exit;
}*/

$id_attraction_actuel = (int) $_GET['id'];

if(isset($_SESSION['id_utilisateur'])){
  $id_utilisateur = $_SESSION['id_utilisateur'];
} else {
  $id_utilisateur = null;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM attraction
    WHERE id_attraction = ?
");
$stmt->execute([$id_attraction_actuel]);

$attraction = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
    UPDATE attraction SET nom = ?, description = ?, taille_minimum = ?, age_minimum = ?, duree_minutes = ?, prix = ? WHERE id_attraction = ?");

    $stmt->execute([$_POST['nom'], $_POST['description'], $_POST['taille_minimum'], $_POST['age_minimum'], $_POST['duree_minutes'], $_POST['prix'], $id_attraction_actuel]);

    $stmt = $pdo->prepare("
    SELECT *
    FROM attraction
    WHERE id_attraction = ?
    ");
    $stmt->execute([$id_attraction_actuel]);
}

/*
$est_favori = false;
if ($id_utilisateur) {
    $chk = $pdo->prepare("SELECT id_attraction FROM Favoris WHERE id_utilisateur = ? AND id_attraction = ?");
    $chk->execute([$id_utilisateur, $id_bien_actuel]);
    $est_favori = (bool)$chk->fetch();
}

if ($attraction && isset($_SESSION['id_utilisateur'])) {
    try {
        $nom_attraction = ($attraction['type_bien'] ?? 'attraction') . ' à ' . ($attraction['ville'] ?? '');

        $pdo->prepare("DELETE FROM HistoriqueBien WHERE id_utilisateur = ? AND id_bien = ?")
            ->execute([$_SESSION['id_utilisateur'], $id_attraction_actuel]);

        $pdo->prepare("
            INSERT INTO HistoriqueAttraction (id_utilisateur, id_bien, nom_bien, image_bien, ville, date_visite)
            VALUES (?, ?, ?, ?, ?, NOW())
        ")->execute([
            $_SESSION['id_utilisateur'],
            $id_attraction_actuel,
            $nom_attraction,
            $bien['image_bien'] ?? '',
            $bien['ville'] ?? ''
        ]);

        $pdo->prepare("
            DELETE FROM HistoriqueAttraction
            WHERE id_utilisateur = ?
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM HistoriqueAttraction
                    WHERE id_utilisateur = ?
                    ORDER BY date_visite DESC
                    LIMIT 20
                ) tmp
            )

        ")->execute([$_SESSION['id_utilisateur'], $_SESSION['id_utilisateur']]);

    } catch (Exception $e) {

    }

}
*/

/*
$requete_type_prefere = "SELECT type_bien, 
(COUNT(Favoris.id_bien) * 2 + COUNT(visites.id_bien)) AS score_preference
FROM Bien
LEFT JOIN Favoris ON Bien.id_bien = Favoris.id_bien AND Favoris.id_utilisateur = ?
LEFT JOIN Visiter AS visites ON Bien.id_bien = visites.id_bien AND visites.id_utilisateur = ?
GROUP BY type_bien
ORDER BY score_preference DESC
LIMIT 1";

$execution_type_prefere = $pdo->prepare($requete_type_prefere);
$execution_type_prefere->execute([$id_utilisateur, $id_utilisateur]);
$resultat_type_prefere = $execution_type_prefere->fetch(PDO::FETCH_ASSOC);
$type_bien_prefere = $resultat_type_prefere['type_bien'];

$requete_suggestions = "SELECT Bien.*, Adresse.* 
         FROM Bien
         JOIN Adresse ON Bien.id_adresse = Adresse.id_adresse
         WHERE type_bien = ?
         AND id_bien != ?
         AND id_bien NOT IN (
             SELECT id_bien FROM Visiter WHERE id_utilisateur = ?
         )
         AND id_bien NOT IN (
             SELECT id_bien FROM Favoris WHERE id_utilisateur = ?
         )
         LIMIT 2";

$execution_suggestions = $pdo->prepare($requete_suggestions);
$execution_suggestions->execute([$type_bien_prefere, $id_bien_actuel, $id_utilisateur, $id_utilisateur]);
$liste_suggestions = $execution_suggestions->fetchAll(PDO::FETCH_ASSOC);
*/

?>

<!doctype html>
<html lang="fr">
  <head>
    <link rel="stylesheet" href="/CSS/style.css" />
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Modification attraction - Wild West Park</title>
    <script src="attraction.js"></script>
    <?php include('../includes/header.php');?>
  </head>
  <body>

    <div class="Card">
      <div class="ImageContainer">
        <div class="ImageBien">
          <?php if ($attraction): ?>
          <img src="<?= (!empty($attraction['image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/images/' . $attraction['image'])) 
    ? '/images/' . htmlspecialchars($attraction['image']) 
    : '/categorie/a.jpg' ?>" alt="Attraction">
          <?php else: ?>
          <p>Bien introuvable</p>
          <?php endif; ?>
        </div>
      </div>

        <form method="POST" action="">
        <div class="Description">
            <label class="location">Nom</label>
            <input type="text" name="nom" value="<?= htmlspecialchars($attraction['nom']) ?? '0'?>">
            <div class="stats">
                <div class="description-text">
                    <label>Description</label>
                    <textarea name="description"><?= htmlspecialchars($attraction['description']) ?? '0'?></textarea>
                </div>
                <div class="room">
                    <label>Taille minimum requise</label>
                    <input type="number" name="taille_minimum" value="<?= htmlspecialchars($attraction['taille_minimum'] ?? '0')?>">
                </div>
                <div class="beach">
                    <label>Âge minimum requis</label>
                    <input type="number" name="age_minimum" value="<?= htmlspecialchars($attraction['age_minimum'] ?? '0')?>">
                </div>
                <div class="surface">
                    <label>Durée (minutes)</label>
                    <input type="number" name="duree_minutes" value="<?= htmlspecialchars($attraction['duree_minutes'] ?? '0')?>">
                </div>
                <div class="cash">
                    <label>Prix</label>
                    <input type="number" step="0.01" name="prix" value="<?= htmlspecialchars($attraction['prix']?? '0')?>">
                </div>
                <div class="surface">
                    <label>Heure d'ouverture</label>
                    <input type="time" name="heure_ouverture" value="<?= htmlspecialchars($attraction['heure_ouverture'] ?? '0')?>">
                </div>
                <div class="surface">
                    <label>Heure de fermeture</label>
                    <input type="time" name="heure_fermeture" value="<?= htmlspecialchars($attraction['heure_fermeture'] ?? '0')?>">
                </div>
                <a href="../backoffice/backoffice.php" class="btn btn-secondary">Retour</a>
                <button type="submit" class="btn btn-warning">Enregistrer</button>
            </div>
        </div>
        </form>

    <script src="/color.js"></script>
  
  </body>
</html>