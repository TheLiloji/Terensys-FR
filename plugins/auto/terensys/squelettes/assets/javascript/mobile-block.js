// Détection des appareils mobiles
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
           (window.innerWidth <= 768 && window.innerHeight <= 1024);
}

// Fonction pour créer l'overlay de blocage mobile
function createMobileBlockOverlay() {
    const overlay = document.createElement('div');
    overlay.id = 'mobile-block-overlay';
    overlay.innerHTML = `
        <img src="/plugins/auto/terensys/squelettes/assets/images/logo-footer.svg" alt="Terensys Logo" class="mobile-logo">
        
        <div class="mobile-block-content">
            <div class="mobile-block-header">
                <h1>Site en cours d'optimisation</h1>
            </div>
            
            <div class="mobile-block-message">
                <p>Notre site est actuellement en cours d'optimisation pour les appareils mobiles.</p>
                <p>Pour une expérience optimale, nous vous recommandons de consulter notre site depuis un ordinateur.</p>
                <p><strong>Vous pouvez nous contacter via :</strong></p>
            </div>
            
            <div class="mobile-block-contact">
                <div class="contact-item">
                    <span class="material-symbols-outlined">mail</span>
                    <div>
                        <strong>Nous écrire</strong>
                        <a href="mailto:contact@terensys.com">contact@terensys.com</a>
                    </div>
                </div>
                
                <div class="contact-item">
                    <span class="material-symbols-outlined">phone</span>
                    <div>
                        <strong>Nous appeler</strong>
                        <a href="tel:+33473742500">04 73 74 25 00</a>
                    </div>
                </div>
                
                <div class="contact-item">
                    <span class="material-symbols-outlined">location_on</span>
                    <div>
                        <strong>Nous rencontrer</strong>
                        <p>22 All. Alan Turing<br>63000 Clermont-Ferrand</p>
                    </div>
                </div>
                

            </div>
            
            <div class="mobile-block-footer">
                <p>Merci de votre compréhension</p>
            </div>
        </div>
    `;
    
    document.body.appendChild(overlay);
    document.body.classList.add('mobile-blocked');
}

// Fonction principale d'initialisation
function initMobileBlock() {
    if (isMobileDevice()) {
        // Attendre que le DOM soit chargé
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', createMobileBlockOverlay);
        } else {
            createMobileBlockOverlay();
        }
    }
}

// Détecter les changements d'orientation et de taille d'écran
window.addEventListener('resize', function() {
    const existingOverlay = document.getElementById('mobile-block-overlay');
    
    if (isMobileDevice() && !existingOverlay) {
        createMobileBlockOverlay();
    } else if (!isMobileDevice() && existingOverlay) {
        existingOverlay.remove();
        document.body.classList.remove('mobile-blocked');
    }
});

// Initialiser dès que le script est chargé
initMobileBlock(); 