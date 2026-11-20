<?php
session_start();

$id_attraction_actuelle = (int) $_GET['id'];



?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include '../includes/header.php'; ?>
    <title>Rédiger un avis - Wild West Park</title>
</head>

<body>

    <div class="Add_bien">
        <h1>Donnez nous votre avis</h1>
    </div>

    <div class="content">
        <form method="POST" action="/interaction_textuelle/verification_avis.php?id=<?= $id_attraction_actuelle ?>" enctype="multipart/form-data">
            <div class="page-layout">

                <div class="col-droite">
                    <label class="champ-label" for="note1">Notes sur 5</label>
                    <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                        <div class="btn-group me-2" role="group" aria-label="First group">

                        <input type="radio" class="btn-check" name="note" id="note1" value="1" autocomplete="off">
                        <label class="btn btn-primary" for="note1">1</label>

                        <input type="radio" class="btn-check" name="note" id="note2" value="2" autocomplete="off">
                        <label class="btn btn-primary" for="note2">2</label>

                        <input type="radio" class="btn-check" name="note" id="note3" value="3" autocomplete="off">
                        <label class="btn btn-primary" for="note3">3</label>

                        <input type="radio" class="btn-check" name="note" id="note4" value="4" autocomplete="off">
                        <label class="btn btn-primary" for="note4">4</label>

                        <input type="radio" class="btn-check" name="note" id="note5" value="5" autocomplete="off">
                        <label class="btn btn-primary" for="note5">5</label>

                        </div>
                    </div>
                    
                    <div class="champ-wrapper">
                        <label for="commentaire" class="form-label">Commentaire</label>
                        <textarea class="form-control" name="commentaire" id="commentaire" maxlength="500" rows="4" placeholder="Partagez votre expérience..." required></textarea>
                        <div class="form-text">
                            <span id="compteur">0</span> / 500 caractères
                        </div>
                    </div>
    
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

                    <div>
                        <input type="number" name="id_attraction_actuelle" value="<?php echo "$id_attraction_actuelle"?>" hidden />
                    </div>
                    <button type="submit" name="ok" class="btn btn-warning">Envoyer</button>
                </div>
            </div>
        </form>
    </div>
    <script src="avis.js"></script>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php') ?>
</body>
</html>
