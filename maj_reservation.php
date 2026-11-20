<?php 

session_start();

if (isset($_SESSION['id_utilisateur'])) {
    $id_utilisateur = $_SESSION['id_utilisateur'];
} else {
    $id_utilisateur = null;
}

require 'session.php';
require 'db.php';
$pdo = getDB();


$donnees = json_decode(file_get_contents('php://input'), true) ?? [];


$id_attraction = (int) ($donnees['id_attraction'] ?? null);
$heure = $donnees['heure'] ?? null;
$nb_personnes = (int) ($donnees['nb_personnes'] ?? null);

if (!$id_attraction || !$heure || !$nb_personnes || $nb_personnes < 1) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'message' => 'Données invalides.']);
    exit;
}

[$date_creneau, $heure_debut] = explode(' ', $heure);

$stmt = $pdo->prepare("SELECT id_creneau, places_restantes FROM creneau WHERE id_attraction = ? AND date_creneau = ? AND heure_debut = ?");
$stmt->execute([$id_attraction, $date_creneau, $heure_debut]);
$creneau = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$creneau) {
    http_response_code(404);
    echo json_encode(['succes' => false, 'message' => 'Créneau introuvable.']);
    exit;
}

if ($creneau['places_restantes'] < $nb_personnes) {
    http_response_code(409);
    echo json_encode(['succes' => false, 'message' => 'Plus assez de places disponibles.']);
    exit;
}

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("UPDATE creneau SET places_restantes = places_restantes - ? WHERE id_creneau = ?");
    $stmt->execute([$nb_personnes, $creneau['id_creneau']]);

    $stmt = $pdo->prepare("INSERT INTO reservation (id_utilisateur, id_creneau, id_attraction, nb_personnes, date_reservation) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$id_utilisateur, $creneau['id_creneau'], $id_attraction, $nb_personnes]);

    $pdo->commit();

    echo json_encode([
        'succes' => true,
        'places_restantes' => $creneau['places_restantes'] - $nb_personnes
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['succes' => false, 'message' => 'Erreur lors de la réservation.']);
}