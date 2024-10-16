document.getElementById('contactForm').addEventListener('submit', function (event) {
    event.preventDefault(); // Empêche le rechargement de la page

    // Limiter à un seul choix pour les prestations
    const prestationCheckboxes = document.querySelectorAll('input[name="prestation[]"]');
    prestationCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (this.checked) {
                prestationCheckboxes.forEach(cb => {
                    if (cb !== this) cb.checked = false; // Décoche les autres
                });
            }
        });
    });

    // Activer/Désactiver les disponibilités si "Être rappelé(e)" est coché
    const rappelCheckbox = document.getElementById('dispo_rappel');
    const disponibiliteCheckboxes = document.querySelectorAll('input[name="disponibilites[]"], #autre_precisez');
    
    rappelCheckbox.addEventListener('change', function () {
        disponibiliteCheckboxes.forEach(cb => {
            cb.disabled = !this.checked; // Active/désactive en fonction de l'état de "Être rappelé(e)"
        });
    });

    // Vérification des champs obligatoires
    const prenom = document.getElementById('prenom').value.trim();
    const nom = document.getElementById('nom').value.trim();
    const email = document.getElementById('email').value.trim();
    const telephone = document.getElementById('telephone').value.trim();
    const prestationChecked = Array.from(prestationCheckboxes).some(cb => cb.checked);

    // Si les champs obligatoires ne sont pas remplis ou aucune prestation n'est cochée
    if (!prenom || !nom || !email || !telephone || !prestationChecked) {
        alert('Veuillez remplir tous les champs obligatoires et choisir une prestation.');
        return;
    }

    document.getElementById('loader').style.display = 'block'; // Affiche le loader
    document.getElementById('successMessage').style.display = 'none'; // Cache le message de succès

    // Création d'un objet FormData
    const formData = new FormData(this);

    // Utilisation de Fetch API pour soumettre le formulaire
    fetch('send_mail.php', { // Change ici pour pointer vers send_mail.php
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
            // COMMENTÉ: La réinitialisation du captcha est temporairement désactivée pour les tests.
            // grecaptcha.reset(); // Réinitialise le captcha
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        document.getElementById('loader').style.display = 'none'; // Cache le loader
        document.getElementById('successMessage').innerText = 'Une erreur s\'est produite. Veuillez réessayer.'; // Message d'erreur
        document.getElementById('successMessage').style.display = 'block'; // Montre le message d'erreur
    });
});
