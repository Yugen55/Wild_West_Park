<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
 
if (!isset($_SESSION['id_role']) || $_SESSION['id_role'] !== 2) {
    header('Location: ../connexion_inscription/connexionv2.php');
    exit;
}
 
require '../db.php';
$pdo = getDB();
 

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM Newsletter WHERE id_newsletter = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: manage_newsletter.php?msg=deleted');
    exit;
}
 
$newsletters = $pdo->query("SELECT * FROM Newsletter ORDER BY date_envoi DESC")->fetchAll(PDO::FETCH_ASSOC);
 

try {
    $nb_abonnes = $pdo->query("SELECT COUNT(*) FROM Abonnes")->fetchColumn();
} catch (Exception $e) {
    $nb_abonnes = '?';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Newsletters - Wild West Park</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: #eee; margin: 0; }
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        h1 { color: #f0c040; font-size: 1.8rem; margin-bottom: 5px; }
        .subtitle { color: #aaa; margin-bottom: 30px; font-size: 0.95rem; }
        .stats-bar {
            display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;
        }
        .stat-card {
            background: #16213e; border: 1px solid #2a2a4a; border-radius: 10px;
            padding: 15px 25px; text-align: center; flex: 1; min-width: 140px;
        }
        .stat-card .val { font-size: 2rem; font-weight: bold; color: #f0c040; }
        .stat-card .lbl { font-size: 0.8rem; color: #aaa; margin-top: 4px; }
        .btn {
            display: inline-block; padding: 10px 22px; border-radius: 8px;
            font-weight: bold; text-decoration: none; cursor: pointer;
            border: none; font-size: 0.9rem; transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.85; }
        .btn-primary { background: #f0c040; color: #1a1a2e; }
        .btn-edit { background: #2196f3; color: #fff; padding: 6px 14px; font-size: 0.82rem; }
        .btn-send { background: #4caf50; color: #fff; padding: 6px 14px; font-size: 0.82rem; }
        .btn-delete { background: #e53935; color: #fff; padding: 6px 14px; font-size: 0.82rem; }
        .nl-table { width: 100%; border-collapse: collapse; background: #16213e; border-radius: 10px; overflow: hidden; }
        .nl-table th { background: #0f3460; padding: 12px 15px; text-align: left; color: #f0c040; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .nl-table td { padding: 12px 15px; border-bottom: 1px solid #2a2a4a; vertical-align: middle; }
        .nl-table tr:last-child td { border-bottom: none; }
        .nl-table tr:hover td { background: #1e2d50; }
        .badge-sent { background: #1b5e20; color: #a5d6a7; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; }
        .badge-draft { background: #4a3000; color: #ffe082; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .alert-success { background: #1b5e20; color: #a5d6a7; }
        .alert-info { background: #0d47a1; color: #90caf9; }
        .back-link { color: #f0c040; text-decoration: none; font-size: 0.9rem; display: inline-block; margin-bottom: 20px; }
        .back-link:hover { text-decoration: underline; }
        .empty { text-align: center; padding: 40px; color: #888; }
    </style>
</head>
<body>
 

 
<div class="container">
    <a href="/backoffice/backoffice.php" class="back-link">Retour au backoffice</a>
    <h1>Gestion des Newsletters</h1>
    <p class="subtitle">Créez, modifiez et envoyez vos newsletters à vos abonnés.</p>
 
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-success">Newsletter supprimée avec succès.</div>
        <?php elseif ($_GET['msg'] === 'sent'): ?>
            <div class="alert alert-success">Newsletter envoyée avec succès !</div>
        <?php elseif ($_GET['msg'] === 'saved'): ?>
            <div class="alert alert-info">Newsletter sauvegardée comme brouillon.</div>
        <?php endif; ?>
    <?php endif; ?>
 
    <div class="stats-bar">
        <div class="stat-card">
            <div class="val"><?= $nb_abonnes ?></div>
            <div class="lbl">Abonnés actifs</div>
        </div>
        <div class="stat-card">
            <div class="val"><?= count($newsletters) ?></div>
            <div class="lbl">Newsletters créées</div>
        </div>
        <div class="stat-card">
            <div class="val">
                <?= count(array_filter($newsletters, fn($n) => !empty($n['date_envoi']))) ?>
            </div>
            <div class="lbl">Envoyées</div>
        </div>
    </div>
 
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <h2 style="margin:0; color:#eee; font-size:1.1rem;">Historique des newsletters</h2>
        <a href="edit_newsletter.php" class="btn btn-primary">+ Nouvelle newsletter</a>
    </div>
 
    <?php if (empty($newsletters)): ?>
        <div class="empty">Aucune newsletter pour l'instant.<br><a href="edit_newsletter.php" class="btn btn-primary" style="margin-top:15px;">Créer la première</a></div>
    <?php else: ?>
    <table class="nl-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Sujet</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <body>
            <?php foreach ($newsletters as $nl): ?>
            <tr>
                <td><?= htmlspecialchars($nl['date_envoi'] ?? '—') ?></td>
                <td style="font-weight:bold;"><?= htmlspecialchars($nl['sujet']) ?></td>
                <td>
                    <?php if (!empty($nl['envoyee'])): ?>
                        <span class="badge-sent"> Envoyée</span>
                    <?php else: ?>
                        <span class="badge-draft">Brouillon</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="actions">
                        <a href="edit_newsletter.php?id=<?= $nl['id_newsletter'] ?>" class="btn btn-edit"> Modifier</a>
                        <a href="edit_newsletter.php?id=<?= $nl['id_newsletter'] ?>&action=send" class="btn btn-send"
                           onclick="return confirm('Envoyer cette newsletter à tous les abonnés ?')">Envoyer</a>
                        <a href="manage_newsletter.php?delete=<?= $nl['id_newsletter'] ?>" class="btn btn-delete"
                           onclick="return confirm('Supprimer définitivement cette newsletter ?')">Supprimer</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
 
</body>
</html>