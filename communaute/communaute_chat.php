<?php 

ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../session.php';
require '../db.php';
$pdo = getDB();

$id_communaute = (int) $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ok'])){
    $contenu_message = trim($_POST['message']);
    $longueur = strlen($contenu_message);

    if ($longueur == 0){
        $erreur = "Le message ne peut pas être vide";
    }
    elseif($longueur > 500){
        $erreur = "Le message est trop long (maximum 500 caractères)";
    }
    else {
        $message_valide = $contenu_message;
        $stmt = $pdo->prepare("INSERT INTO communaute_messages(id_communaute, id_utilisateur, message, date_envoi, supprime) VALUES(?, ?, ?, ?, ?)");
        $stmt->execute([$id_communaute, $_SESSION['id_utilisateur'], $message_valide, date("Y-m-d H:i:s"), 0]);
        header('Location: communaute_chat.php?id=' . $id_communaute);
        exit;
    }
}

$stmt = $pdo->prepare("SELECT communaute_messages.id_utilisateur, communaute_messages.message, communaute_messages.date_envoi,
       utilisateur.nom, utilisateur.prenom, utilisateur.photo_profil
    FROM communaute_messages
    JOIN utilisateur ON communaute_messages.id_utilisateur = utilisateur.id_utilisateur
    WHERE communaute_messages.id_communaute = ?
    ORDER BY communaute_messages.date_envoi ASC");
$stmt->execute([$id_communaute]);
$historique = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT id_sondage, question 
    FROM sondage 
    WHERE id_communaute = ? 
    ORDER BY date_creation DESC 
    LIMIT 1
");
$stmt->execute([$id_communaute]);
$sondage = $stmt->fetch();

$options = [];
$total_global = 0;
$a_vote = false;
$id_sondage = null;

if ($sondage) {
    $id_sondage = $sondage['id_sondage'];

    $stmt = $pdo->prepare("SELECT option_choisie FROM communaute_votes WHERE id_communaute = ? AND id_utilisateur = ?");
    $stmt->execute([$id_communaute, $_SESSION['id_utilisateur']]);
    $vote_utilisateur = $stmt->fetchColumn();
    $a_vote = $vote_utilisateur !== false;
    $stmt = $pdo->prepare("
        SELECT 
            o.id_option, 
            o.libelle,
            COUNT(v.id_vote) AS total_votes
        FROM option_sondage o
        LEFT JOIN communaute_votes v 
            ON v.option_choisie = o.id_option 
            AND v.id_communaute = ?
        WHERE o.id_sondage = ?
        GROUP BY o.id_option, o.libelle
        ORDER BY o.id_option ASC
        ");
$stmt->execute([$id_communaute, $id_sondage]);
$options_brutes = $stmt->fetchAll();

foreach ($options_brutes as $option) {
    $options[] = [
        'id_option' => $option['id_option'],
        'libelle' => $option['libelle'],
        'total_votes' => $option['total_votes'],
        'a_vote_pour' => ((int)$vote_utilisateur === (int)$option['id_option'])
    ];
    $total_global += $option['total_votes'];
}
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat communauté - Wild West Park</title>
</head>
<body class="chat-body">
    <?php include('../includes/header.php'); ?>

    <div class="chat-container">

        <main class="chat-principal">
            <div class="chat-header">
                <h1>Chat de la communauté</h1>
            </div>
            <div class="messages-zone" id="messages-zone">
                <?php foreach($historique as $a): ?>

                    <?php if($a['id_utilisateur'] == $_SESSION['id_utilisateur']): ?>
                        <div class="message message-moi">
                            <div class="bulle">
                                <p class="contenu"><?= htmlspecialchars($a['message']) ?></p>
                                <span class="date"><?= htmlspecialchars($a['date_envoi']) ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="message message-autre">
                            <img src="<?= htmlspecialchars($a['photo'] ?? 'photo.jpg') ?>" class="avatar" alt="avatar">
                            <div class="bulle">
                                <strong class="auteur"><?= htmlspecialchars($a['prenom'] . " " . $a['nom']) ?></strong>
                                <p class="contenu"><?= htmlspecialchars($a['message']) ?></p>
                                <span class="date"><?= htmlspecialchars($a['date_envoi']) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>
                <?php if ($sondage): ?>
<div class="message message-sondage">
    <div class="bulle bulle-sondage">
        <strong class="auteur"> Sondage</strong>
        <p class="question-sondage"><?= htmlspecialchars($sondage['question']) ?></p>

        <form method="POST" action="/communaute/verification_vote.php?id_communaute=<?= $id_communaute ?>">
            <input type="hidden" name="id_sondage" value="<?= $id_sondage ?>">

            <div class="options-sondage">
                <?php foreach ($options as $option): 
                    $pourcentage = $total_global > 0 
                        ? round(($option['total_votes'] / $total_global) * 100) 
                        : 0;
                ?>
                    <div class="option-sondage <?= $option['a_vote_pour'] ? 'option-votee' : '' ?>">
                        <label class="option-label">
                            <input type="radio" name="option_choisie" value="<?= $option['id_option'] ?>"
                                <?= $option['a_vote_pour'] ? 'checked' : '' ?>>
                                <?= htmlspecialchars($option['libelle']) ?>
                        </label>

                        <div class="barre-progression">
                            <div class="barre-remplie" style="width: <?= $pourcentage ?>%;"></div>
                        </div>
                        <span class="pourcentage"><?= $pourcentage ?>% (<?= $option['total_votes'] ?> votes)</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" name="ok" class="btn-voter">
                <?= $a_vote ? 'Modifier mon vote' : 'Voter' ?>
            </button>
        </form>

        <span class="total-votes"><?= $total_global ?> vote<?= $total_global > 1 ? 's' : '' ?> au total</span>
    </div>
</div>
<?php endif; ?>
            </div>

            <form method="POST" action="communaute_chat.php?id=<?= $id_communaute ?>">
                <div class="chat-input-form">
                    <input type="hidden" name="id_utilisateur" value="<?= $_SESSION['id_utilisateur'] ?>">
                    <input type="text" name="message" id="message" class="chat-input" placeholder="Écrivez votre message..." required autocomplete="off">
                    <button type="submit" name="ok" class="btn-envoyer">Envoyer</button>
                </form>
                <form method="POST" action="/communaute/creer_sondage.php?id_communaute=<?= $id_communaute ?>">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                    Créer un sondage
                    </button>

                    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Créer un vote</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                            <div class="modal-body">
        <div class="place">
            <label for="question" class="form-label">Question :</label>
            <input type="text" class="form-control" id="question" name="question" 
                   placeholder="Quelle sortie vous préféreriez ?" required>
        </div>
        <div class="field-block">
            <label>Options</label>
            <input type="text" class="form-control" name="options[]" placeholder="Montagne Russe" required>
            <div class="options-list" id="options-list"></div>
            <button type="button" class="add-option-btn" id="add-option">+ Ajouter une option</button>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
        <button type="submit" name="ok" class="btn btn-primary">Ajouter le sondage</button>
    </div>
                            <script src="vote.js"></script>
                        </div>
                    </div>
                </form>
                </div>
            </div>

        </main>

    </div>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php') ?>
</body>
</html>