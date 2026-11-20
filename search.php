<?php
header('Content-Type: application/json');

require $_SERVER['DOCUMENT_ROOT'] . '/db.php';
$pdo = getDB();

$query = $_GET['q'] ?? '';

if (strlen($query) === 0) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id_attraction, nom AS name FROM attraction WHERE nom LIKE :query LIMIT 10");
    $stmt->execute(['query' => '%' . $query . '%']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

?>