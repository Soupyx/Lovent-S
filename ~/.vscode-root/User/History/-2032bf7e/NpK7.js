document.addEventListener('DOMContentLoaded', function () {
    // Limiter à un seul choix pour les prestations
    const prestationCheckboxes = document.querySelectorAll('input[name="prestation[]"]');
    prestationCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            prestationCheckboxes.forEach(cb => {
                if (cb !== this) {
                    cb.checked = false; // Décoche les autres si l'un est coché
                }
            });
        });
    });

    // Activer/Désactiver les disponibilités si "Être rappelé(e)" est coché
    const rappelCheckbox = document.getElementById('dispo_rappel');
    const disponibiliteCheckboxes = document.querySelectorAll('input[name="disponibilites[]"]');

    rappelCheckbox.addEventListener('change', function () {
        // Les cases de disponibilité restent désactivées si "Être rappelé(e)" est coché
        disponibiliteCheckboxes.forEach(cb => {
            if (cb !== document.getElementById('dispo_autre')) {
                cb.disabled = !this.checked; // Active ou désactive les cases à cocher de disponibilités
            }
        });
        // Assurez-vous que la case "Autre" est activée si "Être rappelé(e)" est coché
        document.getElementById('dispo_autre').disabled = !this.checked;
    });

    // Assurez-vous que la case "Autre" n'est pas désactivée
    const autreCheckbox = document.getElementById('dispo_autre');
    const autreInput = document.getElementById('autre_precisez');

    // Lancer l'activation de l'input "autre_precisez" uniquement si "Autre" est coché
    autreCheckbox.addEventListener('change', function () {
        // Active ou désactive l'input en fonction de la case "Autre"
        autreInput.disabled = !this.checked; 
        if (!this.checked) {
            autreInput.value = ''; // Réinitialise la valeur si "Autre" n'est plus coché
        }
    });

    // Vérification des champs obligatoires lors de la soumission du formulaire
    document.getElementById('contactForm').addEventListener('submit', function (event) {
        event.preventDefault(); // Empêche le rechargement de la page

        // Récupération des valeurs des champs obligatoires
        const prenom = document.getElementById('prenom').value.trim();
        const nom = document.getElementById('nom').value.trim();
        const email = document.getElementById('email').value.trim();
        const telephone = document.getElementById('telephone').value.trim();
        const prestationChecked = Array.from(prestationCheckboxes).some(cb => cb.checked);
        
        // Récupération de la réponse du reCAPTCHA
        const recaptchaResponse = grecaptcha.getResponse();

        // Validation des champs obligatoires
        if (!prenom || !nom || !email || !telephone || !prestationChecked) {
            alert('Veuillez remplir tous les champs obligatoires et choisir une prestation.');
            return; // Arrête la soumission si validation échoue
        }

        // Vérifier si le reCAPTCHA a été validé
        if (!recaptchaResponse) {
            alert("Le captcha doit être fait."); // Alerte si reCAPTCHA n'est pas rempli
            return; // Arrête la soumission si le captcha n'est pas validé
        }

        document.getElementById('loader').style.display = 'block'; // Affiche le loader
        document.getElementById('successMessage').style.display = 'none'; // Cache le message de succès

        // Création d'un objet FormData pour envoyer les données du formulaire
        const formData = new FormData(this);

        // Utilisation de Fetch API pour envoyer les données du formulaire via AJAX
        fetch('send_mail.php', { // Pointer vers le bon fichier PHP
            method: 'POST',
            body: formData
        })
        .then(response => response.text()) // Récupère la réponse en texte
        .then(data => {
            document.getElementById('loader').style.display = 'none'; // Cache le loader
            document.getElementById('successMessage').innerText = data; // Affiche la réponse du serveur
            document.getElementById('successMessage').style.display = 'block'; // Affiche le message de succès

            // Réinitialiser le formulaire si l'envoi a réussi
            if (data.includes('Votre message a été envoyé avec succès.')) {
                document.getElementById('contactForm').reset(); // Réinitialise le formulaire
                disponibiliteCheckboxes.forEach(cb => cb.disabled = true); // Désactive les disponibilités après réinitialisation
                autreInput.disabled = true; // Réactive le champ "Autre" après réinitialisation
                grecaptcha.reset(); // Réinitialise le reCAPTCHA après une soumission réussie
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('loader').style.display = 'none'; // Cache le loader
            document.getElementById('successMessage').innerText = 'Une erreur s\'est produite. Veuillez réessayer.'; // Affiche un message d'erreur
            document.getElementById('successMessage').style.display = 'block'; // Affiche le message d'erreur
        });
    });

    // Assurez-vous que le champ "autre_precisez" est désactivé par défaut
    autreInput.disabled = true; // Désactive le champ par défaut
});
