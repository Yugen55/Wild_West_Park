<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un bien - Wild West Park</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Judson:ital,wght@0,400;0,700;1,400&display=swap">
    <link rel="stylesheet" href="/CSS/style.css">
</head>

<body>
    <?php include('../includes/header.php'); ?>

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
                        <label class="champ-label" for="nom">Nom</label>
                        <textarea id="nom" name="nom" rows="2" placeholder="Donnez lui un nom..." required></textarea>
                    </div>

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

                    <div class="champ-wrapper">
                        <label class="champ-label" for="heure_ouverture">Heure d'ouverture</label>
                        <input type="time" id="heure_ouverture" name="heure_ouverture" required>
                    </div>

                    <div class="champ-wrapper">
                        <label class="champ-label" for="heure_fermeture">Heure de fermeture</label>
                        <input type="time" id="heure_fermeture" name="heure_fermeture" required>
                    </div>

                    <div class="champ-wrapper">
                        <label class="champ-label" for="prix">Prix</label>
                        <input type="number" id="prix" name="prix" placeholder="Ex : 5€" min="0" required>
                    </div>

                    <button type="submit" name="ok" value="1" class="btn btn-info mt-3 Ajout">
                        Ajouter l'attraction
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
</body>
</html>