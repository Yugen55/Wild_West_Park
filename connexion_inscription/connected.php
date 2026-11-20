<?php
session_start();

require '../db.php';
$pdo = getDB();


header('Content-Type: application/json; charset=utf-8');

if (isset($_POST['action']) && $_POST['action'] === 'ping') {

    if (!isset($_SESSION['id_utilisateur'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }

    $check = $pdo->prepare("SELECT force_logout FROM utilisateur WHERE id_utilisateur = :id");
    $check->execute([':id' => $_SESSION['id_utilisateur']]);
    $user = $check->fetch();

    if ($user && $user['force_logout'] == 1) {
        $pdo->prepare("
            UPDATE utilisateur 
            SET force_logout = 0, last_activity = NULL 
            WHERE id_utilisateur = :id
        ")->execute([':id' => $_SESSION['id_utilisateur']]);

        session_destroy();
        echo json_encode(['deconnecte' => true]);
        exit;
    }

    $pdo->prepare("
        UPDATE utilisateur 
        SET last_activity = NOW() 
        WHERE id_utilisateur = :id
    ")->execute([':id' => $_SESSION['id_utilisateur']]);

    echo json_encode(['deconnecte' => false]);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'liste') {

    if (!isset($_SESSION['id_role']) || $_SESSION['id_role'] != 2) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    $stmt = $pdo->query("
        SELECT nom, prenom, email, last_activity, id_role
        FROM utilisateur
        WHERE last_activity >= NOW() - INTERVAL 5 MINUTE
        ORDER BY last_activity DESC
    ");

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'deconnecter') {

    if (!isset($_SESSION['id_role']) || $_SESSION['id_role'] != 2) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    if ($email === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email']);
        exit;
    }

    $pdo->prepare("UPDATE utilisateur SET force_logout = 1 WHERE email = :email")
        ->execute([':email' => $email]);

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
?>