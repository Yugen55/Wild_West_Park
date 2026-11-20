<?php

session_start();

$visitLogPath = "/var/www/html/logbook/log.txt"; 
$today = date('Y/m/d');

$totalVisitsToday = 0;
$uniqueUsersToday = [];
$recentLogs = [];


require '../db.php';
$pdo = getDB();


$requete_newsletter = $pdo->query("
    SELECT id_newsletter, sujet, date_envoi 
    FROM newsletter 
    ORDER BY date_envoi DESC 
    LIMIT 5
");

$stmt = $pdo->query("
    SELECT utilisateur.*, avis.*
    FROM utilisateur
    JOIN avis ON utilisateur.id_utilisateur = avis.id_utilisateur
");
$avis = $stmt->fetchAll();


$stmt = $pdo->query("SELECT id_attraction, nom, image, statut FROM attraction ORDER BY nom ASC");
$attractions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT subquery.reservation_count AS nbvisiteurmax, 
                            attraction.id_attraction, 
                            attraction.nom, 
                            attraction.image
FROM (
  SELECT id_attraction, COUNT(*) AS reservation_count
  FROM reservation 
  GROUP BY id_attraction
) AS subquery
JOIN attraction ON subquery.id_attraction = attraction.id_attraction
ORDER BY subquery.reservation_count DESC
LIMIT 1");
$best_attraction = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM parametres_tarif");
$params = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
  SELECT HOUR(date_reservation) as heure, COUNT(*) as nb
  FROM reservation
  WHERE statut = 'confirmee' AND DATE(date_reservation) = CURDATE()
  GROUP BY HOUR(date_reservation)
");
$donnees = $stmt->fetchAll();

$heures = [];
$nombres = [];

foreach($donnees as $d){
    $heures[] = $d['heure'] . "h";
    $nombres[] = $d['nb'];
}

$heures_json = json_encode($heures);
$nombres_json = json_encode($nombres);



?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Backoffice - Wild West Park</title>
    <link rel="stylesheet" href="/CSS/style.css">
    <style>
        .nl-container { max-width: 1000px; margin: 20px auto; font-family: sans-serif; }
        .nl-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .nl-shortcut { background: #ffc107; color: #000; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .nl-shortcut-alt { border: 2px solid #ffc107; color: #ffc107; padding: 8px 18px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .nl-table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .log-box { background: #fff3cd; border: 1px solid #ffeeba; padding: 15px; border-radius: 8px; margin-bottom: 30px; font-family: monospace; font-size: 0.9rem; }
    </style>
    <link rel="stylesheet" href="/CSS/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include('../includes/header.php'); ?>

<div class="nl-container">

    <?php
    $config = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/config.json'), true);
    $currentTimeout = $config['session_timeout'] ?? 30;
    ?>

    <div style="background:#fff3cd; border:1px solid #ffeeba; padding:15px; border-radius:8px; margin-bottom:30px;">
        <h3 style="margin-top:0;">⏱ Durée de session</h3>
        <p style="color:#666; font-size:0.9rem;">Déconnexion automatique après :</p>
        <div id="timeout-btns" style="display:flex; gap:10px; flex-wrap:wrap;">
            <?php foreach ([5, 10, 15, 30, 60] as $min): ?>
                <button onclick="saveTimeout(<?= $min ?>)"
                    style="padding:8px 20px; border-radius:6px; border:2px solid #08193a; cursor:pointer; font-weight:bold;
                    background:<?= $currentTimeout == $min ? '#08193a' : '#fff' ?>;
                    color:<?= $currentTimeout == $min ? '#c9a84c' : '#08193a' ?>;">
                    <?= $min ?> min
                </button>
            <?php endforeach; ?>
        </div>
        <p style="margin-top:10px; color:#666; font-size:0.85rem;">
            Durée actuelle : <strong id="current-timeout"><?= $currentTimeout ?> minutes</strong>
        </p>
    </div>

    <script>
function saveTimeout(minutes) {
    fetch('/connexion_inscription/save_config.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'duration=' + minutes
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('current-timeout').textContent = minutes + ' minutes';
            // Mettre à jour les boutons
            document.querySelectorAll('#timeout-btns button').forEach(function(btn) {
                var min = parseInt(btn.textContent);
                btn.style.background = min === minutes ? '#08193a' : '#fff';
                btn.style.color = min === minutes ? '#c9a84c' : '#08193a';
            });
        }
    });
}
</script>
    <div style="background:#fff3cd; border:1px solid #ffeeba; padding:15px; border-radius:8px; margin-bottom:30px;">
        <h3 style="margin-top:0;">Connectés en temps réel</h3>
        <div id="connected-users">Chargement...</div>
    </div>

    <script>
function formatDate(dateStr) {
    if (!dateStr) return '-';
    var d = new Date(dateStr.replace(' ', 'T'));
    var day = String(d.getDate()).padStart(2, '0');
    var month = String(d.getMonth() + 1).padStart(2, '0');
    var year = d.getFullYear();
    var hours = String(d.getHours()).padStart(2, '0');
    var minutes = String(d.getMinutes()).padStart(2, '0');
    var seconds = String(d.getSeconds()).padStart(2, '0');
    return day + '/' + month + '/' + year + ' à ' + hours + ':' + minutes + ':' + seconds;
}

function chargerConnectes() {
    fetch('/connexion_inscription/connected.php?action=liste')
        .then(function(r) { return r.json(); })
        .then(function(users) {
            var div = document.getElementById('connected-users');
            if (users.length === 0) { div.innerHTML = '<p>Aucun utilisateur connecté.</p>'; return; }
            var html = '<table style="width:100%;border-collapse:collapse;">';
            html += '<tr style="background:#f8f9fa;"><th style="padding:8px;">Nom</th><th>Email</th><th>Rôle</th><th>Dernière activité</th><th>Action</th></tr>';
            users.forEach(function(u) {
                html += '<tr style="border-top:1px solid #eee;">';
                html += '<td style="padding:8px;">' + u.prenom + ' ' + u.nom + '</td>';
                html += '<td>' + u.email + '</td>';
                html += '<td>' + u.type_utilisateur + '</td>';
                html += '<td>' + formatDate(u.last_activity) + '</td>';
                html += '<td><button onclick="deconnecter(\'' + u.email + '\')" style="background:#d32f2f;color:#fff;border:none;padding:5px 12px;border-radius:4px;cursor:pointer;">Déconnecter</button></td>';
                html += '</tr>';
            });
            html += '</table>';
            div.innerHTML = html;
        });
}

function deconnecter(email) {
    if (!confirm('Déconnecter ' + email + ' ?')) return;
    fetch('/connexion_inscription/connected.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=deconnecter&email=' + encodeURIComponent(email)
    }).then(function() { chargerConnectes(); });
}

chargerConnectes();
setInterval(chargerConnectes, 10000);
</script>

    <div class="nl-header">
        <h2 style="color: #fff; margin-right: auto;">Newsletters</h2>
        <a href="/backoffice/manage_newsletter.php" class="nl-shortcut">Gérer les newsletters</a>
        <a href="/backoffice/edit_newsletter.php" class="nl-shortcut-alt">+ Nouvelle newsletter</a>
    </div>

    <div style="background: #fff; padding: 20px; border-radius: 8px;">
        <p style="color: #666; font-size: 0.9rem; margin-bottom: 15px;">5 dernières newsletters :</p>
        <table class="nl-table">
            <thead>
                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                    <th style="padding: 0.75rem; text-align: left;">Date d'envoi</th>
                    <th style="padding: 0.75rem; text-align: left;">Titre</th>
                    <th style="padding: 0.75rem; text-align: left;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ligne = $requete_newsletter->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td style="padding: 0.75rem; border-top: 1px solid #dee2e6;">
                        <?= $ligne['date_envoi'] ? date('d/m/Y à H:i:s', strtotime(htmlspecialchars($ligne['date_envoi']))) : 'Non envoyée' ?>
                    </td>
                    <td style="padding: 0.75rem; border-top: 1px solid #dee2e6; font-weight: bold;"><?= htmlspecialchars($ligne['sujet']) ?></td>
                    <td style="padding: 0.75rem; border-top: 1px solid #dee2e6;">
                        <a href="/backoffice/edit_newsletter.php?id=<?= htmlspecialchars($ligne['id_newsletter']) ?>" style="color: #1a73e8; text-decoration: none; margin-right: 10px;">Modifier</a>
                        <a href="/backoffice/edit_newsletter.php?id=<?= htmlspecialchars($ligne['id_newsletter']) ?>&action=send" style="color: #d32f2f; text-decoration: none;">Envoyer</a>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div style="text-align: right; margin-top: 15px;">
            <a href="manage_newsletter.php" style="color: #d32f2f; font-size: 0.85rem; font-weight: bold; text-decoration: none;">Voir tout l'historique →</a>
        </div>
    </div>

    <div class="nl-header">
        <h2 style="color: #fff; margin-right: auto;">Gestion des prix</h2>
    </div>

    <div style="background: #fff; padding: 20px; border-radius: 8px;">
    <form method="POST" action="/backoffice/update_tarifs.php">
        <label>Prix de base :</label>
        <input type="number" step="0.01" name="prix_base" value="<?php echo htmlspecialchars($params['prix_base']); ?>"><br>
        <label>Ratio du prix adulte</label>
        <input type="number" step="0.01" name="ratio_adulte" value="<?php echo htmlspecialchars($params['ratio_adulte']); ?>"><br>
        <label>Ratio du prix enfant</label>
        <input type="number" step="0.01" name="ratio_enfant" value="<?php echo htmlspecialchars($params['ratio_enfant']); ?>"><br>
        <label>Ratio du prix senior</label>
        <input type="number" step="0.01" name="ratio_senior" value="<?php echo htmlspecialchars($params['ratio_senior']); ?>"><br>
        <label>Nombre de ticket pour avoir + 1 offert </label>
        <input type="number" step="1" name="nb_tickets_lot" value="<?php echo htmlspecialchars($params['nb_tickets_lot']); ?>"><br>
        <button type="submit" class="btn btn-outline-warning">Mettre à jour</button>
    </form>
                </div>
    <div class="nl-header">
        <h2 style="color: #fff; margin-right: auto;">Meilleur attraction du mois</h2>
    </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px;">
        <table class="nl-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th style="padding: 10px; width: 120px;">Aperçu</th>
                    <th style="padding: 10px;">Nom</th>
                    <th style="padding: 10px; width: 300px; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($best_attraction as $img): ?>
                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                    <td style="padding: 10px; text-align: center; width: 120px;">
                        <img src="/images/<?php echo htmlspecialchars($img['image'] ?? '')?>" 
                            style="height: 80px; object-fit: cover; border-radius: 6px;">
                    </td>
                    <td style="padding: 10px; font-weight: bold;"><?php echo htmlspecialchars($img['nom'])?></td>
                    <td style="padding: 10px; text-align: center; width: 300px;">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
                </div>

    <div class="nl-header">
        <h2 style="color: #fff; margin-right: auto;">Attractions</h2>
    </div>

    <div style="background: #fff; padding: 20px; border-radius: 8px;">
        <table class="nl-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th style="padding: 10px; width: 120px;">Aperçu</th>
                    <th style="padding: 10px;">Nom</th>
                    <th style="padding: 10px; width: 300px; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($attractions as $img): ?>
                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                    <td style="padding: 10px; text-align: center; width: 120px;">
                        <img src="/images/<?php echo htmlspecialchars($img['image'] ?? '')?>" 
                            style="height: 80px; object-fit: cover; border-radius: 6px;">
                    </td>
                    <td style="padding: 10px; font-weight: bold;"><?php echo htmlspecialchars($img['nom'])?></td>
                    <td style="padding: 10px; text-align: center; width: 300px;">
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="/backoffice/edit_attraction.php?id=<?php echo htmlspecialchars($img['id_attraction'])?>" 
                                class="btn btn-primary btn-sm fw-bold">Modifier</a>
                            <a href="/backoffice/supprimer_attraction.php?id=<?php echo htmlspecialchars($img['id_attraction'])?>" 
                                class="btn btn-danger btn-sm fw-bold"
                                onclick="return confirm('Supprimer cette attraction ?')">Supprimer</a>
                            <?php if ($img['statut'] === 'ouverte'): ?>
                                <a href="/backoffice/desactiver_attraction.php?id=<?php echo htmlspecialchars($img['id_attraction'])?>" 
                                class="btn btn-warning btn-sm fw-bold"
                                onclick="return confirm('Fermer cette attraction ?')">Fermer</a>
                            <?php else: ?>
                                <a href="/backoffice/activer_attraction.php?id=<?php echo htmlspecialchars($img['id_attraction'])?>" 
                                class="btn btn-success btn-sm fw-bold"
                                onclick="return confirm('Ouvrir cette attraction ?')">Ouvrir</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    
    </div>

        // à continuer ici :
        <a href="/backoffice/creer_attraction.php" 
                                class="btn btn-dark rounded-pill px-5 py-3 d-block mx-auto"
                                onclick="return confirm('Créer une attraction ?')">Créer</a>
    

    <div class="nl-header">
        <h2 style="color: #fff; margin-right: auto;">Avis</h2>
    </div>

    <div style="background: #fff; padding: 20px; border-radius: 8px;">
        <table class="nl-table" style="width: 100%; border-collapse: collapse;">
            <tbody>
                <?php foreach ($avis as $a): ?>
                    <div class="container-avis">
                        <div class="avis-card">
                            <div class="avis-header">
                                <img src=<?= (!empty($a['photo_profil']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/images/' . $a['photo_profil'])) 
                                ? '/images/' . htmlspecialchars($a['photo_profil']) 
                                : '/images/parchemin.png' ?> alt="Photo de profil" class="avis-photo">
                            <div class="avis-info">
                            <h3 class="avis-nom"><?php echo htmlspecialchars($a['prenom'] . " " . $a['nom']) ?></h3>
                        </div>
                    </div>
                    <p class="avis-texte">
                        <?php echo htmlspecialchars($a['message'])?>
                    </p>
                    <span class="avis-date"><?php echo htmlspecialchars($a['date_avis'])?></span>
                    </div>
                    <a href="/backoffice/supprimer_avis.php?id=<?php echo htmlspecialchars($a['id_avis'])?>" 
                                class="btn btn-danger btn-sm fw-bold"
                                onclick="return confirm('Supprimer cette avis ?')">Supprimer</a>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
                </div>
      
     <div style="background: #fff; padding: 20px; border-radius: 8px;"><canvas id="monGraphique"></canvas></div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const heures = <?php echo htmlspecialchars($heures_json); ?>;
  const nombres = <?php echo htmlspecialchars($nombres_json); ?>;
  
  const ctx = document.getElementById('monGraphique');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: heures,
        datasets: [{
            label: 'Visites',
            data: nombres,
            fill: false,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1,
    }]
  }});
</script>
<script>
let sessionTimeout = <?= $currentTimeout ?? 30 ?> * 60 * 1000;
let inactivityTimeout;

function resetTimer() {
    clearTimeout(inactivityTimeout);
    
    fetch('/connexion_inscription/connected.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=ping'
    })
    .then(r => r.json())
    .then(data => {
        if (data.deconnecte) {
            alert('Vous avez été déconnecté par inactivité.');
            window.location.href = '/logout.php';
        }
    });
    
    inactivityTimeout = setTimeout(() => {
        alert('Votre session a expiré par inactivité.');
        window.location.href = '/logout.php';
    }, sessionTimeout);
}

document.addEventListener('mousemove', resetTimer);
document.addEventListener('keydown', resetTimer);
document.addEventListener('click', resetTimer);

resetTimer();
</script>
</body>
</html>