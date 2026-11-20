<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'session.php';
require 'db.php';
$pdo = getDB();

$tri_options = [
    'age_asc'     => 'age_minimum ASC',
    'age_desc'    => 'age_minimum DESC',
    'taille_asc'  => 'taille_minimum ASC',
    'taille_desc' => 'taille_minimum DESC',
    'place_asc'   => 'places_disponibles ASC',
    'place_desc'  => 'places_disponibles DESC',
];

$tri = $_GET['tri'] ?? 'age_asc';
if (!array_key_exists($tri, $tri_options)) {
    $tri = 'age_asc';
}

$orderby = $tri_options[$tri];

$params = $_GET;

$parPage = 9;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $parPage;

$total = (int)$pdo->query("SELECT COUNT(*) FROM attraction")->fetchColumn();
$pages = (int)ceil($total / $parPage);

$stmt = $pdo->prepare("
    SELECT * FROM attraction
    ORDER BY $orderby
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit',  $parPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();

?>


<body>
    <?php
include 'includes/header.php'; ?>
    <main class="attraction-page section-carte">

        <section class="attraction-hero">
            <div>
                <h1>Attraction</h1>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-warning dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    Filtres
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="?tri=age_asc">age croissant</a></li>
                    <li><a class="dropdown-item" href="?tri=age_desc">age décroissant</a></li>
                    <li><a class="dropdown-item" href="?tri=taille_asc">taille croissante</a></li>
                    <li><a class="dropdown-item" href="?tri=taille_desc">taille décroissante</a></li>
                    <li><a class="dropdown-item" href="?tri=place_asc">Place croissante</a></li>
                    <li><a class="dropdown-item" href="?tri=place_desc">Place décroissante</a></li>
                </ul>
            </div>
        </section>

        <section class="attraction-list">
            <?php foreach ($articles as $a): ?>
                <a href="/attraction.php?id=<?= $a['id_attraction'] ?>">
                    <article class="attraction-card">
                        <div class="attraction-card-image">
                            <img src="<?= (!empty(htmlspecialchars(($a['image']))) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/images/' . $a['image']))
                                            ? '/images/' . htmlspecialchars($a['image'])
                                            : '/a.jpg' ?>"
                                 alt="">
                        </div>
                        <div class="attraction-card-content">
                            <div class="attraction-card-top">
                                <p class="attraction-price">
                                    <?= htmlspecialchars($a['nom'] ?? 'Non renseigné') ?>
                                </p>
                                <div class="attraction-location">
                                    <span>
                                        Localisation<br>
                                        <?= htmlspecialchars($a['age_minimum']        ?? '') ?> ans au minimum ,
                                        <?= htmlspecialchars($a['taille_minimum'] ?? '') ?> cm au minimum
                                    </span>
                                </div>
                            </div>
                            <div class="attraction-meta">
                                <span><?= htmlspecialchars($a['places_disponibles']   ?? '') ?> places disponibles</span>
                                <span><?= htmlspecialchars($a['duree_minutes']    ?? '') ?> minutes</span>
                                <span><?= htmlspecialchars($a['statut'] ?? '') ?></span>
                            </div>
                        </div>
                    </article>
                </a>
            <?php endforeach; ?>

        </section>

        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): 
                    $params['page'] = $page - 1;?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query($params) ?>">Précédent</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pages; $i++): 
                    $params['page'] = $i; ?>
                    <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query($params) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $pages): 
                    $params['page'] = $page + 1; ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query($params) ?>">Suivant</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

    </main>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php') ?>                                                                  
</body>
</html>
