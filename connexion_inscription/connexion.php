<?php
session_start();

//include($_SERVER['DOCUMENT_ROOT'] . '/logbook/logvisit.php');
//addVisitLog(basename($_SERVER['PHP_SELF']));

require '../db.php';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $req = $pdo->prepare("
        SELECT utilisateur.*, role.libelle AS role_libelle
        FROM utilisateur
        JOIN role ON utilisateur.id_role = role.id_role
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

    if (in_array($user['role_libelle'], ['gestionnaire', 'superadmin'])) {
        header("Location: /admin");
        exit;
    } else {
        header("Location: /");
        exit;
    }

} else {
    $erreur = "Email ou mot de passe incorrect.";
}
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Connexion - Wild West Park</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>

<body>
    <div class="selection">
        <div class="box">
            <header>
                <div>
                    <img src="../images/Logo.png" alt="Logo" class="logo">
                </div>
            </header>
            <main class="main_connexion">
                <div class="container">
                    <h2>Connexion</h2>

                    <?php if (!empty($erreur)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
                    <?php endif; ?>

                    <form method="post" action="/connexion">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="text" class="form-control" id="email" name="email" placeholder="Entrez votre E-mail..." required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe:</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Entrez votre mot de passe..." required>
                        </div>
                        <input type="submit" class="btn btn-primary" value="Connexion">
                    </form>
                    <p>Si vous n'avez pas de compte, <a href="/inscription">inscrivez-vous</a></p>
                </div>
            </main>
        </div>
        <div>
            <img src="../images/Illustration_wild_west.webp" alt="Image" class="img_connexion">
        </div>
    </div>
    <script src="/color.js"></script>
</body>
</html>