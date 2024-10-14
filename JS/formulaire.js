document.addEventListener("DOMContentLoaded", () => {
    // Gestion de la soumission du formulaire
    document.getElementById("contactForm").addEventListener("submit", function (event) {
        event.preventDefault();

        // Vérification du reCAPTCHA
        if (grecaptcha.getResponse() === "") {
            alert("Veuillez confirmer que vous n'êtes pas un robot.");
            return;
        }

        const autreCheckbox = document.getElementById("dispo_autre");
        const autreInput = document.getElementById("autre_precisez");

        // Vérification si 'autre' est sélectionné et si un input est fourni
        if (autreCheckbox.checked && !autreInput.value.trim()) {
            alert("Veuillez préciser votre autre disponibilité.");
            return;
        }

        // Validation du formulaire avant l'envoi
        if (!validateForm()) {
            return; // Si la validation échoue, ne pas continuer
        }

        const messageDiv = document.getElementById("message");
        messageDiv.textContent = "Envoi en cours...";
        messageDiv.style.color = "#333";

        // Créer une instance de FormData pour récupérer les données du formulaire
        const formData = new FormData(this);

        // Si la case "Autre" est cochée, ajouter sa valeur à FormData
        if (autreCheckbox.checked) {
            formData.append('disponibilites[]', autreInput.value.trim());
        }

        fetch('send_mail.php', {
            method: 'POST',
            body: formData,
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Une erreur est survenue lors de l\'envoi du formulaire.');
            }
            return response.text();
        })
        .then(data => {
            messageDiv.textContent = "Le message a été envoyé.";
            messageDiv.style.color = "green";
            console.log(data);
        })
        .catch(error => {
            messageDiv.textContent = "Erreur d'envoi du formulaire.";
            messageDiv.style.color = "red";
            console.error(error);
        });
    });

    // Fonction de validation du formulaire
    function validateForm() {
        const checkboxes = document.querySelectorAll('input[name="prestation"]');
        const isChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);

        if (!isChecked) {
            alert("Veuillez sélectionner au moins une prestation.");
            return false; // Empêche l'envoi du formulaire
        }

        return true; // Permet l'envoi du formulaire si au moins une case est cochée
    }

    // Fonction pour activer/désactiver les champs 'autre' en fonction de la checkbox
    const autreCheckbox = document.getElementById('dispo_autre');
    const autreInput = document.getElementById('autre_precisez');
    autreCheckbox.addEventListener('change', toggleInput);

    function toggleInput() {
        // Active l'input si la checkbox est cochée, sinon la désactive
        autreInput.disabled = !autreCheckbox.checked;
    }

    // Initialiser l'état des checkboxes
    const disponibilitesCheckboxes = document.querySelectorAll('input[name="disponibilites[]"]');
    const rappelCheckbox = document.getElementById('dispo_rappel');

    // Gérer l'activation des disponibilités
    rappelCheckbox.addEventListener('change', toggleDisponibilites);

    function toggleDisponibilites() {
        disponibilitesCheckboxes.forEach(checkbox => {
            checkbox.disabled = !rappelCheckbox.checked;
            checkbox.parentElement.classList.toggle('disabled-checkbox', !rappelCheckbox.checked);
        });
    }

    // Mise à jour des styles des prestations
    const prestationsCheckboxes = document.querySelectorAll('input[name="prestation"]');
    prestationsCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', updatePrestationStyles);
    });

    function updatePrestationStyles() {
        prestationsCheckboxes.forEach((checkbox) => {
            const label = checkbox.nextElementSibling; // Get the corresponding label

            // Si la case est cochée
            if (checkbox.checked) {
                // Désactiver les autres cases
                prestationsCheckboxes.forEach((cb) => {
                    if (cb !== checkbox) {
                        cb.disabled = true; // Désactiver les autres cases
                        cb.parentElement.classList.add('dimmed'); // Ajouter la classe dimmed
                        cb.checked = false; // Décocher les autres cases
                    }
                });
                label.style.opacity = '1'; // Rendre l'étiquette sélectionnée complètement opaque
            } else {
                // Si la case est décochée, réactiver toutes les cases
                checkbox.disabled = false; // Réactiver la case actuelle
                label.style.opacity = '0.5'; // Diminuer l'opacité des étiquettes non sélectionnées
            }
        });

        // Gestion de la désélection
        if (Array.from(prestationsCheckboxes).every(cb => !cb.checked)) {
            prestationsCheckboxes.forEach((cb) => {
                cb.disabled = false; // Réactiver toutes les cases si aucune n'est cochée
                cb.parentElement.classList.remove('dimmed'); // Supprimer la classe dimmed
                cb.nextElementSibling.style.opacity = '1'; // Restaurer l'opacité de toutes les étiquettes
            });
        }
    }

    // Initialiser l'état des inputs au chargement
    toggleInput();
    toggleDisponibilites();
});
