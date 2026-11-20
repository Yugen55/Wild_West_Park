<?php
session_start();


require '../db.php';
$pdo = getDB();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $req = $pdo->prepare("
        SELECT utilisateur.*, id_role
        FROM utilisateur
        WHERE utilisateur.email = :email
    ");
    $req->execute([':email' => $email]);
    $user = $req->fetch();
    if ($user && password_verify($password, $user['mot_de_passe'])) {
        $_SESSION['id_utilisateur'] = $user['id_utilisateur'];
        $_SESSION['id_role'] = $user['id_role'];
        $_SESSION['email'] = $user['email'];

    $pdo->prepare("
        UPDATE utilisateur 
        SET last_activity = NOW(), derniere_connexion = NOW() 
        WHERE id_utilisateur = :id
    ")->execute([':id' => $user['id_utilisateur']]);

    if (in_array($user['id_role'], ['2'])) {
        header("Location: ../backoffice/backoffice.php");
        exit;
    } else {
        header("Location: ../index.php");
        exit;
    }

} else {
    $erreur = "Email ou mot de passe incorrect.";
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
    <title>Connexion - Wild West Park</title>
</head>

<body class="illustrafond">
    <div class="rectConnexion">
        <button type="button" class="btn btn-light btn-retour" onclick="history.back()">
            <i class="bi bi-arrow-left"></i> Retour
        </button>
        <h1>Connexion</h1>
        <?php if (!empty($erreur)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>
        <div><img src="../images/Logo.png" alt="Logo" class="logo"></div>
        <div class="content">
            <form method="post" action="connexionv2.php">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="text" class="form-control" id="email" name="email" placeholder="Entrez votre E-mail..." required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe:</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Entrez votre mot de passe..." required>
                    </div>
                    <input type="submit" class="btn btn-primary" value="Connexion">
            
            <p>Si vous n'avez pas de compte, <a href="/connexion_inscription/inscriptionv2.php" class="link-info link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">inscrivez-vous</a></p>
            </form>
        </div>
    </div>
</body>
</html>