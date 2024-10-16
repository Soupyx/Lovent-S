document.getElementById('contactForm').addEventListener('submit', function (event) {
    event.preventDefault(); // Empêche le rechargement de la page

    // Vérifie si le captcha est coché
    if (grecaptcha.getResponse() === "") {
        alert("Veuillez confirmer que vous n'êtes pas un robot."); // Alerte l'utilisateur
        return; // Ne continue pas si le captcha n'est pas coché
    }

    document.getElementById('loader').style.display = 'block'; // Affiche le loader
    document.getElementById('successMessage').style.display = 'none'; // Cache le message de succès

    // Création d'un objet FormData
    const formData = new FormData(this);

    // Utilisation de Fetch API pour soumettre le formulaire
    fetch('contact.php', { // Change ici pour pointer vers contact.php
        method: 'POST',
        body: formData
    })
    .then(response => response.text()) // Récupère la réponse en texte
    .then(data => {
        document.getElementById('loader').style.display = 'none'; // Cache le loader
        document.getElementById('successMessage').innerText = data; // Affiche la réponse
        document.getElementById('successMessage').style.display = 'block'; // Montre le message de succès
        
        // Réinitialiser le formulaire si le message est un succès
        if (data.includes('Votre message a été envoyé avec succès.')) {
            document.getElementById('contactForm').reset(); // Réinitialise le formulaire
            grecaptcha.reset(); // Réinitialise le captcha
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        document.getElementById('loader').style.display = 'none'; // Cache le loader
        document.getElementById('successMessage').innerText = 'Une erreur s\'est produite. Veuillez réessayer.'; // Message d'erreur
        document.getElementById('successMessage').style.display = 'block'; // Montre le message d'erreur
    });
});
