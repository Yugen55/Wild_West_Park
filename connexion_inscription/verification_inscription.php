<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
require '../db.php';
$pdo = getDB();

if (isset($_POST['nom'])) {
    $nom        = trim($_POST['nom']);
    $prenom     = trim($_POST['prenom']);
    $email      = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $mdp        = $_POST['password'];
    $newsletter = (isset($_POST['subscribe']) && $_POST['subscribe'] === 'yes') ? 1 : 0;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Email invalide.");
    }


    $check = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE email = :email");
    $check->execute([":email" => $email]);
    $existe = $check->fetchColumn();

    if ($existe > 0) {
        echo "Cet email est déjà utilisé !";
    } else {

        $id_role = $pdo->query("SELECT id_role FROM role WHERE libelle = 'client'")->fetchColumn();

        if (!$id_role) {
            die("Erreur : le rôle 'client' n'existe pas dans la table role.");
        }

        $token      = bin2hex(random_bytes(32));
        $expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $requete = $pdo->prepare("INSERT INTO utilisateur 
            (nom, prenom, email, mot_de_passe, newsletter, id_role, token_verification, token_expire, confirme) 
            VALUES 
            (:nom, :prenom, :email, :password, :newsletter, :id_role, :token, :expiration, 0)");

        $requete->execute([
            ":nom"        => $nom,
            ":prenom"     => $prenom,
            ":email"      => $email,
            ":password"   => password_hash($mdp, PASSWORD_DEFAULT),
            ":newsletter" => $newsletter,
            ":id_role"    => $id_role,
            ":token"      => $token,
            ":expiration" => $expiration
        ]);

        $lien = "https://altoprestige.fr/confirmer?token=" . $token;

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

            $mail->setFrom('altosprestige@gmail.com', 'Wild West Park');
            $mail->addAddress($email, $prenom);

            $mail->Subject = "Confirmez votre inscription - Wild West Park";
            $mail->Body    = "Bonjour $prenom,\n\n"
                           . "Merci de vous être inscrit sur Wild West Park.\n\n"
                           . "Cliquez sur ce lien pour confirmer votre compte :\n\n"
                           . $lien . "\n\n"
                           . "Ce lien expire dans 24h.\n\n"
                           . "L'équipe de Wild West Park";

            $mail->send();

            header("Location: confirmation_envoyee.php");
            exit();

        } catch (Exception $e) {
            die("Erreur envoi mail : " . $mail->ErrorInfo);
        }
    }
}