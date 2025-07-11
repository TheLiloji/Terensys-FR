document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contact-form');
    const message = document.getElementById('sended');
    const sujetSelect = document.querySelector('select[name="sujet_message_auteur"]');
    const precisionAutre = document.getElementById('precision-autre');
    const supportPopup = document.getElementById('support-popup');
    const closePopupBtn = document.getElementById('close-popup');
    const continueFormBtn = document.getElementById('continue-form');

    // Vérifier que tous les éléments existent
    if (!sujetSelect || !supportPopup || !closePopupBtn || !continueFormBtn) {
        console.error('Certains éléments du formulaire de contact sont manquants');
        return;
    }

    // Gestion de l'affichage du champ "Précision" quand "Autre" est sélectionné
    sujetSelect.addEventListener('change', function() {
        if (this.value === 'Autre') {
            precisionAutre.style.display = 'block';
            precisionAutre.required = true;
        } else {
            precisionAutre.style.display = 'none';
            precisionAutre.required = false;
            precisionAutre.value = '';
        }
        
        // Afficher la popup pour Support technique
        if (this.value === 'Support technique') {
            supportPopup.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Empêcher le scroll
        }
    });

    // Fermer la popup
    closePopupBtn.addEventListener('click', function() {
        supportPopup.classList.add('hidden');
        document.body.style.overflow = 'auto';
        // Réinitialiser la sélection
        sujetSelect.value = '';
    });

    // Continuer avec le formulaire
    continueFormBtn.addEventListener('click', function() {
        supportPopup.classList.add('hidden');
        document.body.style.overflow = 'auto';
        // Garder la sélection "Support technique"
    });

    // Fermer la popup en cliquant sur l'arrière-plan
    supportPopup.addEventListener('click', function(e) {
        if (e.target === supportPopup) {
            supportPopup.classList.add('hidden');
            document.body.style.overflow = 'auto';
            sujetSelect.value = '';
        }
    });

    // Soumission du formulaire
    if (form) {
        form.addEventListener('submit', async function() {
            if (message) {
                message.classList.remove('hidden');
                setTimeout(() => {
                    message.classList.add('hidden');
                }, "5000");
            }
        });
    }
});