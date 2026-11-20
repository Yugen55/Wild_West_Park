<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


ini_set('display_errors', 1);
error_reporting(E_ALL);


if (isset($_SESSION['id_utilisateur'])){
    $lien = "/connexion_inscription/connexionv2.php";
    $lien2 = "/achat_ticket.php";
    $lien3 = "/communaute/communaute.php";
} 
else {
    $lien = "/connexion_inscription/connexionv2.php";
    $lien2 = "/connexion_inscription/connexionv2.php";
    $lien3 = "/connexion_inscription/connexionv2.php";
}

$configPath = $_SERVER['DOCUMENT_ROOT'] . '/config.json';
$config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
$timeout = $config['session_timeout'] ?? 30;


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
    <link rel="stylesheet" href="/CSS/style.css">
    <link rel="icon" type="image/png" href="/images/Logo.png">
    <title>Document</title>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand" href="/index.php">
      <img src="../images/Logo.png" width="200" height="200">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="../liste_attraction.php">Attraction</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../concours_photo/concours_main.php">Concours photo</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?php echo htmlspecialchars($lien3) ?>">Communauté</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?php echo htmlspecialchars($lien2) ?>">Billetterie</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?php echo htmlspecialchars($lien)?>">Connexion</a>
        </li>
        <?php if (isset($_SESSION['id_utilisateur'])): ?>
          <li class="nav-item">
            <a href="/connexion_inscription/logout.php" class="btn btn-danger rounded-pill px-4 shadow-sm">Déconnexion</a>
          </li>
        <?php endif; ?>
        <?php if (isset($_SESSION['id_role']) && ($_SESSION['id_role'] == 2)): ?>
          <li class="nav-item">
            <a href="/backoffice/backoffice.php" role="button" style="color:white; font-weight:bold; font-size:0.85rem; text-decoration:none; background:#c9a84c; color:#08193a; padding:5px 12px; border-radius:20px;">
                    Admin
            </a>
          </li>
        <?php endif; ?>
      </ul>
      <form class="d-flex position-relative" role="search" id="searchForm">
  <input class="form-control me-2" type="search" placeholder="Rechercher" aria-label="Search" id="searchInput"/>
  <button class="btn btn-outline-success" type="submit">Rechercher</button>
  <ul id="results" class="list-group position-absolute w-100"></ul>
</form>
    </div>
  </div>
</nav>
<script src="/API_fetch/recherche.js"></script>
</body>
</html>
 
