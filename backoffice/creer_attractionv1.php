<?php
session_start();
//include($_SERVER['DOCUMENT_ROOT'] . '/logbook/logvisit.php');
//addVisitLog(basename($_SERVER['PHP_SELF']));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include '../includes/header.php'; ?>
    <title>Ajouter une attraction</title>
</head>


<body>

    <div class="Add_bien">
        <h1>Ajouter une attraction</h1>
    </div>

    <div class="content">
        <form method="POST" action="/backoffice/verification.php" enctype="multipart/form-data">
            <div class="page-layout">

                <div class="col-gauche">
                    <div id="drop-zone" class="drop border border-warning rounded-5">
                        <label class="Add_files">
                            Ajouter un fichier ou <br> glissez-le ici
                            <input type="file" id="file-input" name="image" accept="image/*" hidden>
                        </label>
                        <ul id="preview"></ul>
                        <p class="recommendation mt-2 mb-0" style="font-size:0.85rem;">
                            Nous vous recommandons d'utiliser des fichiers
                            .jpg de grande qualité de moins de 20 Mo ou .mp4
                            de moins de 200 Mo.
                        </p>
                    </div>
                </div>

                <div class="col-droite">

                    <div class="champ-wrapper">
                        <label class="champ-label" for="description">Description</label>
                        <textarea id="description" name="description" rows="4" placeholder="Décrivez l'attraction..." required></textarea>
                    </div>

                    <div class="champ-wrapper">
                        <label class="champ-label" for="age_minimum">Age minimum</label>
                        <input type="number" id="age_minimum" name="age_minimum" placeholder="Ex : 12" min="0" required>
                    </div>

                    <div class="champ-wrapper">
                        <label class="champ-label" for="taille_minimum">Taille minimum en cm</label>
                        <input type="number" id="taille_minimum" name="taille_minimum" placeholder="Ex : 170" min="0" required>
                    </div>

                    <div class="champ-wrapper">
                        <label class="champ-label" for="places_disponibles">Places disponibles</label>
                        <input type="number" id="places_disponibles" name="places_disponibles" placeholder="Ex : 20" min="0" required>
                    </div>

                    <div class="champ-wrapper">
                        <label class="champ-label" for="duree_minutes">Durée en minutes</label>
                        <input type="number" id="duree_minutes" name="duree_minutes" placeholder="Ex : 60" min="0" required>
                    </div>

                    <button type="submit" name="ok" value="1" class="btn btn-info mt-3 Ajout">
                        Ajouter l'attraction
                    </button>
                </div>
            </div>
        </form>
    </div>
    <?php include '../includes/footer.php'; ?>
    <script src="../color.js"></script>
</body>
</html>
