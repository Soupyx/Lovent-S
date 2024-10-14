<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Chargement de Composer et des dépendances
require 'vendor/autoload.php';

// Chargement des variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$mail = new PHPMailer(true);

// Clé secrète reCAPTCHA
$secretKey = '6LfholwqAAAAAFiqk0nQRJWbCcKVUZj8akTeaBqZ'; // Remplacez par votre propre clé

// Démarrer la session
session_start();

// Vérifiez si la méthode de requête est POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérifiez que tous les champs requis sont présents
    if (isset($_POST['prenom'], $_POST['nom'], $_POST['email'], $_POST['telephone'], $_POST['g-recaptcha-response'])) {
        // Récupération et validation des données du formulaire
        $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

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
            echo ' Détails : ' . implode(', ', $responseKeys['error-codes']); // Afficher les erreurs
            exit;
        }

        // Vérifiez que les checkboxes sont valides (si c'est applicable)
        $prestations = isset($_POST['prestation']) && is_array($_POST['prestation']) ? array_map('htmlspecialchars', $_POST['prestation']) : [];

        $disponibilites = isset($_POST['disponibilites']) ? array_map('htmlspecialchars', $_POST['disponibilites']) : [];

        // Vérifiez si "Autre" est sélectionné
        if (in_array('Autre', $disponibilites)) {
            if (isset($_POST['autre_precisez']) && !empty(trim($_POST['autre_precisez']))) {
                $autrePrecisez = filter_input(INPUT_POST, 'autre_precisez', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $disponibilites[] = $autrePrecisez; // Ajoute la valeur précisée
            } else {
                echo 'Veuillez préciser votre disponibilité pour "Autre".';
                exit;
            }
        }

        // Supprimer "Autre" de la liste des disponibilités si elle est présente
        $disponibilites = array_filter($disponibilites, function ($value) {
            return $value !== 'Autre';
        });

        // Enlever les doublons dans les disponibilités
        $disponibilites = array_unique($disponibilites);

        // Configuration de PHPMailer
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Serveur SMTP Gmail
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME']; // Utiliser la variable d'environnement
            $mail->Password = $_ENV['MAIL_PASSWORD']; // Utiliser la variable d'environnement
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Sécurisation
            $mail->Port = 587; // Port SMTP

            // Destinataire
            $mail->setFrom($_ENV['MAIL_USERNAME'], 'Lovents'); // Adresse de l'expéditeur
            $mail->addAddress($_ENV['MAIL_TO']); // Remplacez par l'adresse du destinataire

            // Contenu de l'e-mail
            $mail->isHTML(true); // Format HTML
            $mail->Subject = 'Nouvelle demande de contact';
            $mail->Body = "Vous avez reçu un nouveau message de <b>{$prenom} {$nom}</b>.<br>
                           <b>Email:</b> {$email}<br>
                           <b>Téléphone:</b> {$telephone}<br>
                           <b>Prestations choisies:</b> " . implode(', ', $prestations) . "<br>
                           <b>Disponibilités:</b> " . implode(', ', $disponibilites);

            // Envoi de l'e-mail
            $mail->send();
            echo 'Le message a été envoyé.';
        } catch (Exception $e) {
            echo "Le message n'a pas pu être envoyé. Erreur : {$mail->ErrorInfo}";
        }
    } else {
        echo 'Veuillez remplir tous les champs requis.';
    }
} else {
    echo 'Méthode de requête non valide.';
}
