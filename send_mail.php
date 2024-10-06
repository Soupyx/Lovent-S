<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Inclure Composer

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$mail = new PHPMailer(true);

// Chargement des variables d'environnement
$mail->Username = $_ENV['MAIL_USERNAME'];
$mail->Password = $_ENV['MAIL_PASSWORD'];
$secretKey = $_ENV['RECAPTCHA_SECRET'];

// Fonction pour générer un jeton CSRF
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérification du jeton CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo 'Échec de la validation CSRF.';
        exit;
    }

    // Vérifiez le reCAPTCHA
    $recaptchaResponse = $_POST['g-recaptcha-response'];

    // Initialiser cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);

    // Les données à envoyer dans la requête
    $data = [
        'secret' => $secretKey,
        'response' => $recaptchaResponse
    ];

    // Attacher les données au cURL
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

    // Exécuter la requête cURL
    $response = curl_exec($ch);
    $responseKeys = json_decode($response, true);

    // Vérifier si cURL a réussi
    if (curl_errno($ch)) {
        error_log('Erreur cURL: ' . curl_error($ch));
        echo 'Une erreur s\'est produite. Veuillez réessayer plus tard.';
        exit;
    }

    // Fermer la connexion cURL
    curl_close($ch);

    // Si le reCAPTCHA n'est pas validé
    if (intval($responseKeys["success"]) !== 1) {
        echo "Veuillez prouver que vous n'êtes pas un robot.";
        exit;
    }

    // Vérifiez quel formulaire a été soumis
    if (isset($_POST['prenom'], $_POST['nom'], $_POST['email'], $_POST['telephone'])) {
        // Récupération et validation des données du formulaire
        $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING);
        $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING);
        
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

        // Vérifiez que les checkboxes sont valides (si c'est applicable)
        $prestations = isset($_POST['prestation']) ? array_map('htmlspecialchars', $_POST['prestation']) : [];
        $disponibilites = isset($_POST['disponibilites']) ? array_map('htmlspecialchars', $_POST['disponibilites']) : [];

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
            $mail->Body = buildEmailBody($prenom, $nom, $email, $telephone, $prestations, $disponibilites);
            $mail->AltBody = buildEmailAltBody($prenom, $nom, $email, $telephone, $prestations, $disponibilites);

            $mail->send();
            echo 'Votre message a été envoyé avec succès.';
        } catch (Exception $e) {
            // Log the error without exposing sensitive information
            error_log("Erreur lors de l'envoi de l'email: {$mail->ErrorInfo}");
            echo 'Une erreur s\'est produite lors de l\'envoi de votre message. Veuillez réessayer plus tard.';
        }
    } else {
        echo 'Tous les champs sont obligatoires.';
    }
} else {
    http_response_code(405);
    echo 'Méthode non autorisée.';
}

/**
 * Fonction pour construire le corps de l'email
 */
function buildEmailBody($prenom, $nom, $email, $telephone, $prestations, $disponibilites) {
    return sprintf(
        "<h1>Demande de contact / rendez-vous</h1>
        <p><strong>Prénom:</strong> %s</p>
        <p><strong>Nom:</strong> %s</p>
        <p><strong>Email:</strong> %s</p>
        <p><strong>Téléphone:</strong> %s</p>
        <p><strong>Prestations:</strong> %s</p>
        <p><strong>Disponibilités:</strong> %s</p>",
        htmlspecialchars($prenom),
        htmlspecialchars($nom),
        htmlspecialchars($email),
        htmlspecialchars($telephone),
        htmlspecialchars(implode(', ', array_map('htmlspecialchars', $prestations))),
        htmlspecialchars(implode(', ', array_map('htmlspecialchars', $disponibilites)))
    );
}

/**
 * Fonction pour construire le corps alternatif de l'email
 */
function buildEmailAltBody($prenom, $nom, $email, $telephone, $prestations, $disponibilites) {
    return sprintf(
        "Prénom: %s\nNom: %s\nEmail: %s\nTéléphone: %s\nPrestations: %s\nDisponibilités: %s",
        htmlspecialchars($prenom),
        htmlspecialchars($nom),
        htmlspecialchars($email),
        htmlspecialchars($telephone),
        htmlspecialchars(implode(', ', array_map('htmlspecialchars', $prestations))),
        htmlspecialchars(implode(', ', array_map('htmlspecialchars', $disponibilites)))
    );
}
?>
