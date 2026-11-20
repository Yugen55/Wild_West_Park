<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['id_role']) || $_SESSION['id_role'] !== 2) {
    header('Location: ../connexion_inscription/connexionv2.php');
    exit;
}

require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../db.php';
$pdo = getDB();

$newsletter = ['id_newsletter' => null, 'sujet' => '', 'contenu' => ''];
$message = '';
$message_type = '';


if (isset($_GET['id_newsletter']) && is_numeric($_GET['id_newsletter'])) {
    $stmt = $pdo->prepare("SELECT * FROM Newsletter WHERE id_newsletter = ?");
    $stmt->execute([$_GET['id_newsletter']]);
    $nl = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($nl) $newsletter = $nl;
}


$abonnes = [];
try {
    $abonnes = $pdo->query("SELECT * FROM Utilisateur WHERE newsletter = 1 ORDER BY email ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $sujet         = trim($_POST['sujet'] ?? '');
    $contenu       = trim($_POST['contenu'] ?? '');
    $action        = $_POST['action'] ?? 'save';
    $dest          = $_POST['destinataires'] ?? 'all';
    $emails_custom = $_POST['emails_custom'] ?? '';

    if (empty($sujet) || empty($contenu)) {
        $message      = "Le sujet et le contenu sont obligatoires.";
        $message_type = 'error';
    } else {

      
        if (!empty($newsletter['id_newsletter'])) {
            $stmt = $pdo->prepare("UPDATE Newsletter SET sujet = ?, contenu = ?, date_envoi = NOW() WHERE id_newsletter = ?");
            $stmt->execute([$sujet, $contenu, $newsletter['id_newsletter']]);
            $id_nl = $newsletter['id_newsletter'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO Newsletter (sujet, contenu, date_envoi) VALUES (?, ?, NOW())");
            $stmt->execute([$sujet, $contenu]);
            $id_nl = $pdo->lastInsertId();
        }

        if ($action === 'send') {

            
            $liste_emails = [];
            if ($dest === 'all') {
                foreach ($abonnes as $ab) {
                    $liste_emails[] = ['email' => $ab['email'], 'prenom' => $ab['prenom'] ?? ''];
                }
            } elseif ($dest === 'custom') {
                $raw = preg_split('/[\s,;]+/', $emails_custom);
                foreach ($raw as $em) {
                    $em = trim($em);
                    if (filter_var($em, FILTER_VALIDATE_EMAIL)) {
                        $liste_emails[] = ['email' => $em, 'prenom' => ''];
                    }
                }
            }

            
            $corps_html = nl2br(htmlspecialchars($contenu));
            $html_email = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='font-family:Georgia,serif; background:#f9f5ec; margin:0; padding:0;'>
  <div style='max-width:600px; margin:40px auto; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 15px rgba(0,0,0,0.08);'>
    <div style='background:#1a1a2e; padding:30px; text-align:center;'>
      <h1 style='color:#f0c040; margin:0; font-size:1.6rem; letter-spacing:2px;'>WILD WEST</h1>
    </div>
    <div style='padding:35px; color:#333; line-height:1.8;'>
      <h2 style='color:#1a1a2e; border-bottom:2px solid #f0c040; padding-bottom:10px;'>" . htmlspecialchars($sujet) . "</h2>
      <div style='margin-top:20px;'>" . $corps_html . "</div>
    </div>
    <div style='background:#f2f2f2; padding:20px; text-align:center; font-size:0.8rem; color:#888;'>
      Wild West &bull; <a href='https://altoprestige.fr' style='color:#888;'>altoprestige.fr</a>
    </div>
  </div>
</body>
</html>";

            
            $nb_envoyes = 0;
            $nb_erreurs = 0;

            foreach ($liste_emails as $dest_info) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'altosprestige@gmail.com';
                    $mail->Password   = 'kqjckrqowjoiuylg';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;
                    $mail->CharSet    = 'UTF-8';

                    $mail->setFrom('altosprestige@gmail.com', 'Wild West');
                    $mail->addAddress($dest_info['email'], $dest_info['prenom']);
                    $mail->isHTML(true);
                    $mail->Subject = $sujet;
                    $mail->Body    = $html_email;
                    $mail->AltBody = strip_tags($contenu);


                    $mail->send();
                    $nb_envoyes++;
                } catch (Exception $e) {
                    $nb_erreurs++;
                }
            }

            
            $pdo->prepare("UPDATE Newsletter SET envoyee = 1 WHERE id_newsletter = ?")->execute([$id_nl]);

            header("Location: manage_newsletter.php?msg=sent&nb=" . $nb_envoyes . "&err=" . $nb_erreurs);
            exit;

        } else {
            header("Location: manage_newsletter.php?msg=saved");
            exit;
        }
    }

    $newsletter['sujet']   = $sujet ?? '';
    $newsletter['contenu'] = $contenu ?? '';
}

$prefill_send = isset($_GET['action']) && $_GET['action'] === 'send' && !empty($newsletter['id_newsletter']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title><?= $newsletter['id_newsletter'] ? 'Modifier' : 'Créer' ?> une newsletter - Wild West Park</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: #eee; margin: 0; }
        .container { max-width: 800px; margin: 40px auto; padding: 0 20px 60px; }
        h1 { color: #f0c040; font-size: 1.7rem; margin-bottom: 5px; }
        label { display: block; margin: 18px 0 6px; color: #ccc; font-size: 0.9rem; font-weight: 600; }
        input[type=text], textarea {
            width: 100%; box-sizing: border-box;
            background: #16213e; border: 1px solid #2a2a4a; border-radius: 8px;
            color: #eee; padding: 11px 14px; font-size: 0.95rem;
            transition: border-color 0.2s;
        }
        input[type=text]:focus, textarea:focus { outline: none; border-color: #f0c040; }
        textarea { min-height: 200px; resize: vertical; font-family: 'Segoe UI', sans-serif; line-height: 1.6; }
        .section-card { background: #16213e; border: 1px solid #2a2a4a; border-radius: 12px; padding: 25px; margin-top: 25px; }
        .section-title { color: #f0c040; font-size: 1rem; font-weight: bold; margin-bottom: 15px; border-bottom: 1px solid #2a2a4a; padding-bottom: 10px; }
        .radio-group { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 10px; }
        .radio-option { display: flex; align-items: center; gap: 8px; background: #0f3460; border: 2px solid transparent; border-radius: 8px; padding: 10px 18px; cursor: pointer; transition: border-color 0.2s; }
        .radio-option:has(input:checked) { border-color: #f0c040; }
        .radio-option input { accent-color: #f0c040; }
        .abonnes-list { max-height: 180px; overflow-y: auto; background: #0f3460; border-radius: 8px; padding: 10px; margin-top: 10px; border: 1px solid #2a2a4a; }
        .abonnes-list label { display: flex; align-items: center; gap: 8px; margin: 5px 0; font-size: 0.85rem; color: #ccc; font-weight: normal; }
        .btn-actions { display: flex; gap: 15px; margin-top: 30px; flex-wrap: wrap; }
        .btn { padding: 12px 28px; border-radius: 8px; font-weight: bold; border: none; cursor: pointer; font-size: 0.95rem; transition: opacity 0.2s; }
        .btn:hover { opacity: 0.88; }
        .btn-save { background: #2196f3; color: #fff; }
        .btn-send { background: #4caf50; color: #fff; font-size: 1rem; }
        .btn-cancel { background: transparent; color: #aaa; border: 1px solid #444; text-decoration: none; display: inline-flex; align-items: center; padding: 12px 28px; border-radius: 8px; font-weight: bold; }
        .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .alert-error { background: #7f0000; color: #ffcdd2; }
        .back-link { color: #f0c040; text-decoration: none; font-size: 0.9rem; display: inline-block; margin-bottom: 20px; }
        .back-link:hover { text-decoration: underline; }
        #custom-area { display: none; }
        .preview-box { background: #0f3460; border-radius: 8px; padding: 20px; margin-top: 15px; font-size: 0.85rem; color: #ccc; line-height: 1.7; white-space: pre-wrap; min-height: 80px; }
        .counter { font-size: 0.75rem; color: #888; text-align: right; margin-top: 4px; }
    </style>
</head>
<body>

<div class="container">
    <a href="manage_newsletter.php" class="back-link">← Retour aux newsletters</a>
    <h1><?= $newsletter['id_newsletter'] ? 'Modifier la newsletter' : 'Nouvelle newsletter' ?></h1>

    <?php if ($message): ?>
        <div class="alert alert-error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="section-card">
            <div class="section-title">Contenu de la newsletter</div>

            <label for="sujet">Sujet de l'email *</label>
            <input type="text" id="sujet" name="sujet"
                   value="<?= htmlspecialchars($newsletter['sujet']) ?>"
                   placeholder="Ex : Nos nouveaux biens du mois…" required>

            <label for="contenu">Corps du message *</label>
            <textarea id="contenu" name="contenu"
                      placeholder="Rédigez votre message ici…"
                      required><?= htmlspecialchars($newsletter['contenu']) ?></textarea>
            <div class="counter" id="char-count">0 caractères</div>

            <label>Aperçu en temps réel</label>
            <div class="preview-box" id="preview-box">Commencez à rédiger pour voir l'aperçu…</div>
        </div>

        <div class="section-card">
            <div class="section-title">Destinataires</div>

            <div class="radio-group">
                <label class="radio-option">
                    <input type="radio" name="destinataires" value="all" checked onchange="toggleDest(this)">
                    Tous les abonnés à la newsletter (<?= count($abonnes) ?>)
                </label>
                <label class="radio-option">
                    <input type="radio" name="destinataires" value="custom" onchange="toggleDest(this)">
                    Emails personnalisés
                </label>
            </div>

            <div id="custom-area" style="margin-top:15px;">
                <label for="emails_custom">Adresses email (séparées par virgule ou retour à la ligne)</label>
                <textarea id="emails_custom" name="emails_custom" rows="4"
                    placeholder="client1@example.com, client2@example.com"></textarea>
            </div>

            <?php if (!empty($abonnes)): ?>
            <details style="margin-top:15px;">
                <summary style="cursor:pointer; color:#90caf9; font-size:0.85rem;">Voir la liste des abonnés (<?= count($abonnes) ?>)</summary>
                <div class="abonnes-list">
                    <?php foreach ($abonnes as $ab): ?>
                        <label>
                            <input type="checkbox" disabled> <?= htmlspecialchars($ab['prenom'] . ' ' . $ab['nom']) ?> — <?= htmlspecialchars($ab['email']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </details>
            <?php endif; ?>
        </div>

        <div class="btn-actions">
            <button type="submit" name="action" value="save" class="btn btn-save">
                Sauvegarder en brouillon
            </button>
            <button type="submit" name="action" value="send" class="btn btn-send"
                    onclick="return confirm('Envoyer cette newsletter maintenant à tous les destinataires ?')">
                Envoyer maintenant
            </button>
            <a href="manage_newsletter.php" class="btn btn-cancel">Annuler</a>
        </div>

    </form>
</div>

<script>
function toggleDest(radio) {
    document.getElementById('custom-area').style.display =
        radio.value === 'custom' ? 'block' : 'none';
}

var contenu = document.getElementById('contenu');
var preview = document.getElementById('preview-box');
var counter = document.getElementById('char-count');

function updatePreview() {
    var txt = contenu.value;
    counter.textContent = txt.length + ' caractères';
    preview.textContent = txt || 'Commencez à rédiger pour voir l\'aperçu…';
}
contenu.addEventListener('input', updatePreview);
updatePreview();

<?php if (!empty($prefill_send)): ?>
document.querySelector('[name="action"][value="send"]').focus();
<?php endif; ?>
</script>

</body>
</html>