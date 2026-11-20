<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une communauté - Wild West Park</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Judson:ital,wght@0,400;0,700;1,400&display=swap">
    <link rel="stylesheet" href="/CSS/style.css">
</head>

<body>
    <?php include('../includes/header.php'); ?>

    <div class="Add_bien">
        <h1>Créer une communauté</h1>
    </div>

    <div class="content">
        <form method="POST" action="verification_communaute.php" enctype="multipart/form-data">
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
                            .jpg de grande qualité de moins de 20 Mo.
                        </p>
                    </div>
                </div>

                <div class="col-droite">

                    <div class="champ-wrapper">
                        <label class="champ-label" for="titre">Titre de la sortie</label>
                        <textarea id="titre" name="titre" rows="2" placeholder="Donnez lui un nom..." required></textarea>
                    </div>

                    <div class="champ-wrapper">
                        <label class="champ-label" for="description">Description</label>
                        <textarea id="description" name="description" rows="4" placeholder="Décrivez la sortie..." required></textarea>
                    </div>

                    <div class="champ-wrapper">
                        <label class="champ-label" for="date_sortie">Date et heure de la sortie</label>
                        <input type="datetime-local" id="date_sortie" name="date_sortie" required>
                    </div>

                    <div class="champ-wrapper">
                        <label class="champ-label" for="nb_places_max">Nombre de places maximum</label>
                        <input type="number" id="nb_places_max" name="nb_places_max" placeholder="Ex : 15" min="1" required>
                    </div>

                    <button type="submit" name="ok" value="1" class="btn btn-info mt-3 Ajout">
                        Créer la communauté
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        const dropZone  = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const preview   = document.getElementById('preview');

        dropZone.addEventListener('click', (e) => {
            if (e.target !== fileInput) fileInput.click();
        });

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-primary');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('border-primary');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-primary');
            handleFiles(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', () => handleFiles(fileInput.files));

        function handleFiles(files) {
            preview.innerHTML = '';
            Array.from(files).forEach(file => {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = (e) => {
                    const li  = document.createElement('li');
                    const img = document.createElement('img');
                    img.src   = e.target.result;
                    img.style.cssText = 'max-width:100px;max-height:100px;margin:4px;border-radius:8px;';
                    li.appendChild(img);
                    preview.appendChild(li);
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php') ?>
</body>
</html>