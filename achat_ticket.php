<?php 
session_start();
require 'db.php';
$pdo = getDB();

$stmt = $pdo->query("SELECT * FROM parametres_tarif");
$params = $stmt->fetch(PDO::FETCH_ASSOC);


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achat de ticket - Wild West Park</title>
</head>
<body class="illustrabillet">
    <?php include('includes/header.php');?>
        <div class="achat-ticket">
            <div class="billet">
                <h1>Réservez vos billets</h1>
                <div class="alert alert-warning" role="alert">
                    OFFRE SPÉCIAL ÉTÉ : <br> <?php echo $params['nb_tickets_lot']?> tickets achetés et 1 offert
                </div>

                <div id="bandeau-promo" class="alert alert-success">
                 Vous économisez <span id="economies">0</span> € avec cette offre !
            </div>
            <form method="POST" action="">
                <div class="place">
                    <label for="nb_personne" class="form-label">Nombre d'adulte</label>
                    <input type="number" id="nb_adulte" class="form-control" min="1" value="0">
                </div>
                <div class="place">
                    <label for="nb_personne" class="form-label">Nombre d'enfant</label>
                    <input type="number" id="nb_enfant" class="form-control" min="1" value="0">
                </div>
                <div class="place">
                    <label for="nb_personne" class="form-label">Nombre de senior</label>
                    <input type="number" id="nb_senior" class="form-control" min="1" value="0">
                </div>
                <div class="jour">
                    <label for="nb_jour" class="form-label">Nombre de jours</label>
                    <input type="number" id="nb_jour" class="form-control" min="1" value="1">
                </div>
                <div class="prix_total">
                    <h2 >Prix total :</h2>
                    <p> <span id="prix">0</span> €</p>
                    <button onclick="achat_ticket()" class="btn btn-dark rounded-pill px-5 py-3 d-block mx-auto">Calculer le prix</button>
                </div>
                <div>
                    
                    <button type="submit" class="btn btn-warning rounded-pill px-5 py-3 d-block mx-auto">
                        Acheter mes billets
                    </button>
                    
                </div>
                </form>
            </div>
        </div>
    <script>
        let prix_base = <?php echo $params['prix_base']; ?>;
        let ratio_age_adulte = <?php echo $params['ratio_adulte']; ?>;
        let ratio_age_enfant = <?php echo $params['ratio_enfant']; ?>;
        let ratio_age_senior = <?php echo $params['ratio_senior']; ?>;
        let nb_tickets_lot = <?php echo $params['nb_tickets_lot']?>;

        function achat_ticket() {
    let nb_adulte = Number(document.getElementById("nb_adulte").value);
    let nb_enfant = Number(document.getElementById("nb_enfant").value);
    let nb_senior = Number(document.getElementById("nb_senior").value);
    let nb_jours = Number(document.getElementById("nb_jour").value);
    

    let total_personnes = nb_adulte + nb_enfant + nb_senior;

    let prix_sans_promo = prix_base * 
        (nb_adulte * ratio_age_adulte +
         nb_enfant * ratio_age_enfant +
         nb_senior * ratio_age_senior) *
        nb_jours;

    let prix_final = prix_sans_promo;
    let economies = 0;

    if (total_personnes >= nb_tickets_lot) {
        let groupes_complets = Math.floor(total_personnes / nb_tickets_lot);
        let personnes_restantes = total_personnes % nb_tickets_lot;

        let ratio_moyen = (nb_adulte * ratio_age_adulte +
                          nb_enfant * ratio_age_enfant +
                          nb_senior * ratio_age_senior) / total_personnes;

        let prix_groupe = prix_base * 3 * ratio_moyen * nb_jours;

        let prix_restant = prix_base * personnes_restantes * ratio_moyen * nb_jours;

        prix_final = (groupes_complets * prix_groupe) + prix_restant;
        economies = prix_sans_promo - prix_final;

        document.getElementById("bandeau-promo").style.display = "block";
        document.getElementById("economies").textContent = economies.toFixed(2);
    } else {
        document.getElementById("bandeau-promo").style.display = "none";
    }

    document.getElementById("prix").textContent = prix_final.toFixed(2);
}
        window.addEventListener('load', achat_ticket);
    </script>
    
</body>
</html>