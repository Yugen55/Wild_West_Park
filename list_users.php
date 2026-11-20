<?php

$host = '127.0.0.1';
$db   = 'webhunterdb';
$user = 'webhunteradmin';
$pass = '$.webhunter.$1012';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );

    echo "<h2>Liste des utilisateurs</h2>";

    $stmt = $pdo->query("SELECT * FROM utilisateur");

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nom</th><th>Email</th><th>Créé le</th></tr>";

    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . $row['id_utilisateur'] . "</td>";
        echo "<td>" . htmlspecialchars($row['nom']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "</tr>";
    }

    echo "</table>";

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}

?>
