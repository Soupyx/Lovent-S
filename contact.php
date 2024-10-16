<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Inclure Composer

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$mail = new PHPMailer(true);

// Chargement des variables d'environnement
$mail->Username = $_ENV['MAIL_USERNAME'];
$mail->Password = $_ENV['MAIL_PASSWORD'];

$secretKey = '6LfholwqAAAAAFiqk0nQRJWbCcKVUZj8akTeaBqZ'; // Votre clé secrète reCAPTCHA

// Démarrer la session
session_start();

// Vérifiez si la méthode de requête est POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérifiez que tous les champs requis sont présents
    if (isset($_POST['prenom'], $_POST['nom'], $_POST['email'], $_POST['telephone'], $_POST['service'], $_POST['g-recaptcha-response'])) {

        // Récupération et validation des données du formulaire
        $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $service = filter_input(INPUT_POST, 'service', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        // Validation des entrées
        if (strlen($prenom) > 50 || strlen($nom) > 50) {
            echo 'Le prénom et le nom doivent contenir moins de 50 caractères.';
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo 'Adresse e-mail invalide.';
            exit;
        }
        if (!preg_match('/^\+?[0-9]{10,15}$/', $telephone)) {
            echo 'Numéro de téléphone invalide.';
            exit;
        }

        // Vérification du reCAPTCHA
        $captcha = $_POST['g-recaptcha-response'];
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$captcha}");
        $responseKeys = json_decode($response, true);

        if (intval($responseKeys["success"]) !== 1) {
            echo 'Veuillez prouver que vous n\'êtes pas un robot.';
            exit;
        }

        // Configuration de PHPMailer
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Serveur SMTP Gmail
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME']; // Utiliser la variable d'environnement
            $mail->Password = $_ENV['MAIL_PASSWORD']; // Utiliser la variable d'environnement
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Définir l'adresse de l'expéditeur
            $mail->setFrom($_ENV['MAIL_USERNAME'], 'Life\'s Events');
            $mail->addAddress($_ENV['MAIL_USERNAME']); // Adresse de réception des emails

            // Ajouter un reply-to
            $mail->addReplyTo($email, htmlspecialchars($prenom . ' ' . $nom));

            // Contenu du mail
            $mail->isHTML(true);
            $mail->Subject = 'Nouveau message de contact ou rendez-vous';
            $mail->Body = buildEmailBody($prenom, $nom, $email, $telephone, $service);
            $mail->AltBody = buildEmailAltBody($prenom, $nom, $email, $telephone, $service);

            $mail->send();
            echo 'Votre message a été envoyé avec succès.'; // Message de succès
        } catch (Exception $e) {
            // Log the error without exposing sensitive information
            error_log("Erreur lors de l'envoi de l'email: {$mail->ErrorInfo}");
            echo 'Une erreur s\'est produite lors de l\'envoi de votre message. Veuillez réessayer plus tard.'; // Message d'erreur
        }
    } else {
        echo 'Tous les champs sont obligatoires.'; // Message si des champs sont manquants
    }
} else {
    http_response_code(405);
    echo 'Méthode non autorisée.'; // Message si la méthode n'est pas POST
}

/**
 * Fonction pour construire le corps de l'email
 */
function buildEmailBody($prenom, $nom, $email, $telephone, $service) {
    return sprintf(
        "<h1>Rendez-vous</h1>
        <p><strong>Prénom:</strong> %s</p>
        <p><strong>Nom:</strong> %s</p>
        <p><strong>Email:</strong> %s</p>
        <p><strong>Téléphone:</strong> %s</p>
        <p><strong>Service souhaité:</strong> %s</p>",
        htmlspecialchars($prenom),
        htmlspecialchars($nom),
        htmlspecialchars($email),
        htmlspecialchars($telephone),
        html_entity_decode($service)
    );
}

/**
 * Fonction pour construire le corps alternatif de l'email
 */
function buildEmailAltBody($prenom, $nom, $email, $telephone, $service) {
    return sprintf(
        "Prénom: %s\nNom: %s\nEmail: %s\nTéléphone: %s\nService souhaité: %s",
        htmlspecialchars($prenom),
        htmlspecialchars($nom),
        htmlspecialchars($email),
        htmlspecialchars($telephone),
        html_entity_decode($service)
    );
}
?>
