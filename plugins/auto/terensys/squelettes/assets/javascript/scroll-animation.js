// Animation des cercles au scroll
document.addEventListener('DOMContentLoaded', function() {
    const scrollCircles = document.querySelectorAll('.scroll-circle');
    let wasAtTop = true; // Pour tracker si on était en haut
    
    // Configuration de l'Intersection Observer
    const observerOptions = {
        root: null, // viewport
        rootMargin: '0px 0px -200px 0px', // trigger plus tard pour éviter que ce soit trop tôt
        threshold: 0.3 // 30% de l'élément visible pour être plus précis
    };

    // Fonction callback pour l'observer
    const handleIntersect = (entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Ajouter la classe d'animation
                entry.target.classList.add('animate-in');
            } else {
                // Retirer la classe pour rejouer l'animation si on scroll back
                entry.target.classList.remove('animate-in');
            }
        });
    };

    // Créer l'observer
    const observer = new IntersectionObserver(handleIntersect, observerOptions);
    
    // Observer chaque cercle
    scrollCircles.forEach(circle => {
        observer.observe(circle);
    });

    // Animation de parallax supplémentaire au scroll + reset depuis le haut
    let ticking = false;
    
    const updateParallax = () => {
        const scrollY = window.scrollY;
        
        // Détecter si on est tout en haut (moins de 100px du top)
        const isAtTop = scrollY < 100;
        
        // Si on revient du haut vers le bas, réinitialiser l'animation
        if (wasAtTop && !isAtTop) {
            scrollCircles.forEach(circle => {
                // Forcer la suppression de la classe pour réinitialiser
                circle.classList.remove('animate-in');
                // Petit délai pour s'assurer que le CSS a le temps de se réinitialiser
                setTimeout(() => {
                    // Vérifier si le cercle est visible pour relancer l'animation
                    const rect = circle.getBoundingClientRect();
                    const isVisible = rect.top < window.innerHeight && rect.bottom > 0;
                    if (isVisible) {
                        circle.classList.add('animate-in');
                    }
                }, 50);
            });
        }
        
        // Mettre à jour le statut
        wasAtTop = isAtTop;
        
        // Animation parallax normale
        scrollCircles.forEach((circle, index) => {
            if (circle.classList.contains('animate-in')) {
                // Facteurs de parallax réduits pour éviter qu'ils sortent de la box
                const parallaxSpeed = 0.03 + (index * 0.01); // Vitesse réduite
                let yPos = -(scrollY * parallaxSpeed);
                
                // Limiter le déplacement pour qu'ils restent dans la zone visible
                const maxMove = 50; // Maximum 50px de déplacement
                yPos = Math.max(-maxMove, Math.min(maxMove, yPos));
                
                // Appliquer le parallax limité
                circle.style.transform = `translateY(${yPos}px)`;
            }
        });
        
        ticking = false;
    };

    const requestTick = () => {
        if (!ticking) {
            requestAnimationFrame(updateParallax);
            ticking = true;
        }
    };

    // Écouter le scroll pour l'effet parallax et le reset
    window.addEventListener('scroll', requestTick);
}); 