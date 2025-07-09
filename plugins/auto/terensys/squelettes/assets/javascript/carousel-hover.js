document.addEventListener('DOMContentLoaded', function() {
    const carouselImages = document.querySelectorAll('.carousel img');
    const allCarousels = document.querySelectorAll('.carousel');
    
    carouselImages.forEach(img => {
        img.addEventListener('mouseenter', function() {
            // Mettre en pause TOUS les carrousels
            allCarousels.forEach(carousel => {
                carousel.classList.add('paused');
            });
        });
        
        img.addEventListener('mouseleave', function() {
            // Relancer TOUS les carrousels
            allCarousels.forEach(carousel => {
                carousel.classList.remove('paused');
            });
        });
    });
}); 