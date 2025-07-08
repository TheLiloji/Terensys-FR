// Gestion du hover sur les images du carrousel
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner toutes les images des carrousels
    const carouselImages = document.querySelectorAll('.carousel img');
    
    carouselImages.forEach(img => {
        // Trouver le carrousel parent
        const carousel = img.closest('.carousel');
        
        // Événement mouseenter (survol)
        img.addEventListener('mouseenter', function() {
            carousel.classList.add('paused');
        });
        
        // Événement mouseleave (sortie du survol)
        img.addEventListener('mouseleave', function() {
            carousel.classList.remove('paused');
        });
    });
}); 