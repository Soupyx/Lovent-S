<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Inclure Composer

// Charger le fichier .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configurer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE); // Ignore les notices

// Démarrer la session uniquement si elle n'est pas déjà active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$mail = new PHPMailer(true);

// Chargement des variables d'environnement
$mail->Username = $_ENV['MAIL_USERNAME'];
$mail->Password = $_ENV['MAIL_PASSWORD'];

// Vérifiez si la méthode de requête est POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérifiez que tous les champs requis sont présents
    if (isset($_POST['prenom'], $_POST['nom'], $_POST['email'], $_POST['telephone'])) {

        // Récupération et validation des données du formulaire
        $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $prestations = isset($_POST['prestation']) ? $_POST['prestation'] : [];
        $rappel = isset($_POST['rappel']) ? 'Oui' : 'Non';
        $disponibilites = isset($_POST['disponibilites']) ? $_POST['disponibilites'] : [];
        $autre_precisez = isset($_POST['autre_precisez']) ? filter_input(INPUT_POST, 'autre_precisez', FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';

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
            $mail->Subject = 'Nouveau message de contact';
            $mail->Body = buildEmailBody($prenom, $nom, $email, $telephone, $prestations, $rappel, $disponibilites, $autre_precisez);
            $mail->AltBody = buildEmailAltBody($prenom, $nom, $email, $telephone, $prestations, $rappel, $disponibilites, $autre_precisez);

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
function buildEmailBody($prenom, $nom, $email, $telephone, $prestations, $rappel, $disponibilites, $autre_precisez) {
    $prestations_list = implode(', ', $prestations);
    $disponibilites_list = implode(', ', $disponibilites);
    
    return sprintf(
        "<h1>Nouveau message de contact</h1>
        <p><strong>Prénom:</strong> %s</p>
        <p><strong>Nom:</strong> %s</p>
        <p><strong>Email:</strong> %s</p>
        <p><strong>Téléphone:</strong> %s</p>
        <p><strong>Prestations souhaitées:</strong> %s</p>
        <p><strong>Être rappelé:</strong> %s</p>
        <p><strong>Disponibilités:</strong> %s</p>
        <p><strong>Autre précision:</strong> %s</p>",
        htmlspecialchars($prenom),
        htmlspecialchars($nom),
        htmlspecialchars($email),
        htmlspecialchars($telephone),
        htmlspecialchars($prestations_list),
        htmlspecialchars($rappel),
        htmlspecialchars($disponibilites_list),
        htmlspecialchars($autre_precisez)
    );
}

/**
 * Fonction pour construire le corps alternatif de l'email
 */
function buildEmailAltBody($prenom, $nom, $email, $telephone, $prestations, $rappel, $disponibilites, $autre_precisez) {
    $prestations_list = implode(', ', $prestations);
    $disponibilites_list = implode(', ', $disponibilites);

    return sprintf(
        "Prénom: %s\nNom: %s\nEmail: %s\nTéléphone: %s\nPrestations souhaitées: %s\nÊtre rappelé: %s\nDisponibilités: %s\nAutre précision: %s",
        htmlspecialchars($prenom),
        htmlspecialchars($nom),
        htmlspecialchars($email),
        htmlspecialchars($telephone),
        htmlspecialchars($prestations_list),
        htmlspecialchars($rappel),
        htmlspecialchars($disponibilites_list),
        htmlspecialchars($autre_precisez)
    );
}
