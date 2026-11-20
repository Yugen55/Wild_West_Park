<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db.php';
$pdo = getDB();

$erreur = '';

if (isset($_POST['ok'])) {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $mdp = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $newsletter = (isset($_POST['subscribe']) && $_POST['subscribe'] === 'yes') ? 1 : 0;

    try {
        $checkEmail = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = :email");
        $checkEmail->execute([':email' => $email]);

        if ($checkEmail->fetch()) {
            $erreur = "Cet email est déjà utilisé.";
        } else {
            $id_role = $pdo->query("SELECT id_role FROM role WHERE libelle = 'client'")->fetchColumn();

            $sql = "INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, newsletter, id_role) 
                    VALUES (:nom, :prenom, :email, :mdp, :newsletter, :id_role)";
            $requete = $pdo->prepare($sql);
            $requete->execute([
                ":nom" => $nom,
                ":prenom" => $prenom,
                ":email" => $email,
                ":mdp" => $mdp,
                ":newsletter" => $newsletter,
                ":id_role" => $id_role
            ]);

            $id_nouvel_utilisateur = $pdo->lastInsertId();

            $_SESSION['id_utilisateur'] = $id_nouvel_utilisateur;
            $_SESSION['id_role'] = $id_role;
            $_SESSION['email'] = $email;

            header("Location: ../index.php");
            exit;
        }

    } catch (Exception $e) {
        die("Erreur SQL : " . $e->getMessage());
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Judson:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
    <title>Inscription - Wild West Park</title>
</head>

<body class="illustrafond">
    <div class="rectInscription">
        <button type="button" class="btn btn-light btn-retour" onclick="history.back()">
            <i class="bi bi-arrow-left"></i> Retour
        </button>
        <h1>Inscription</h1>
        <div><img src="../images/Logo.png" alt="Logo" class="logo"></div>
        <div class="content">
            <form method="post" action="verification_inscription.php">
                <label>Nom:</label>
                <input type="text" name="nom" class="form-control" required />

                <label>Prénom:</label>
                <input type="text" name="prenom" class="form-control" required />

                <label>Email:</label>
                <input type="email" name="email" class="form-control" required />

                <label>Mot de passe:</label>
                <input type="password" name="password" class="form-control" required />

                <div class="form-check mt-2">
                    <input type="checkbox" name="subscribe" value="yes" class="form-check-input" />
                    <label class="form-check-label">
                        S'inscrire à la newsletter
                    </label>
                </div>
                <input type="submit" id="btnSubmit" class="btn btn-success mt-3" value="S'inscrire" />
            </form>
        </div>
    </div>
</body>
</html>