<?php 
session_start();

require '../db.php';
$pdo = getDB();





?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achat de ticket - Wild West Park</title>
</head>
<body class="illustrabillet">
    <?php include('../includes/header.php');?>
        <div class="achat-ticket">
            <div class="billet">
                <form method="POST" action="/communaute/verification_vote.php">
                <h1>Créer un vote</h1>
                <div class="place">
                     <label for="email" class="form-label">Question :</label>
                    <input type="text" class="form-control" id="question" name="question" placeholder="Quelle sortie vous préféreriez ?" required>
                </div>
                <div class="field-block">
                    <label>Options</label>
                    <input type="text" class="form-control" id="question" name="question" placeholder="Quelle sortie vous préféreriez ?" required>
                    <div class="options-list" id="options-list"></div>
                    <button type="button" class="add-option-btn" id="add-option">+ Ajouter une option</button>
                </div>
                </form>
            </div>
        </div>
    <script src="/achat_ticket.js"></script>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php') ?>
</body>
</html>