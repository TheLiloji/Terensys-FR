// Animation du logo Terensys
document.addEventListener('DOMContentLoaded', function() {
    // Attendre que le SVG soit chargé
    setTimeout(function() {
        startLogoAnimation();
    }, 300);
    
    // Si ça ne marche pas, essayer encore
    setTimeout(function() {
        startLogoAnimation();
    }, 800);
});

function loadSVGInline() {
    // Chercher l'image du logo
    const logoImg = document.querySelector('#banner img.header');
    
    if (logoImg && logoImg.src.includes('logo-banner-animated.svg')) {
        // Charger le SVG via fetch
        fetch(logoImg.src)
            .then(response => response.text())
            .then(svgContent => {
                // Remplacer l'image par le SVG
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = svgContent;
                const svg = tempDiv.querySelector('svg');
                
                if (svg) {
                    // Conserver les classes et styles de l'image
                    svg.className = logoImg.className;
                    
                    
                    // Remplacer l'image par le SVG
                    logoImg.parentNode.replaceChild(svg, logoImg);
                    
                    // Démarrer l'animation immédiatement
                    setTimeout(() => {
                        startActualAnimation();
                    }, 50);
                }
            })
            .catch(error => {
                // Erreur silencieuse
            });
    }
}

function startActualAnimation() {
    // Vérifier que le SVG existe maintenant
    const svg = document.querySelector('#logo-animated');
    
    if (!svg) {
        return;
    }
    
    // Test de récupération des éléments
    const logoPart1Test = document.querySelector('#logo-part-1');
    const logoPart1Test2 = document.querySelector('#logo-animated #logo-part-1');
    
    // Si les éléments sont trouvés, commencer l'animation
    if (logoPart1Test || logoPart1Test2) {
        startAnimationSequence();
    }
}

function startAnimationSequence() {
    // Animation des parties du logo (0.2s, 0.4s, 0.6s)
    setTimeout(() => {
        const logoPart1 = document.querySelector('#logo-animated #logo-part-1');
        if (logoPart1) {
            logoPart1.classList.add('animate-logo');
        }
    }, 200);
    
    setTimeout(() => {
        const logoPart2 = document.querySelector('#logo-animated #logo-part-2');
        if (logoPart2) {
            logoPart2.classList.add('animate-logo');
        }
    }, 400);
    
    setTimeout(() => {
        const logoPart3 = document.querySelector('#logo-animated #logo-part-3');
        if (logoPart3) {
            logoPart3.classList.add('animate-logo');
        }
    }, 600);
    
    // Animation des lettres T-E-R-E-N-S-Y-S
    const letters = [
        { id: 'letter-t-1', delay: 800 },
        { id: 'letter-t-2', delay: 950 },
        { id: 'letter-e-1', delay: 1100 },
        { id: 'letter-r', delay: 1250 },
        { id: 'letter-e-2', delay: 1400 },
        { id: 'letter-n', delay: 1550 },
        { id: 'letter-s-1', delay: 1700 },
        { id: 'letter-y', delay: 1850 },
        { id: 'letter-s-2', delay: 2000 }
    ];
    
    letters.forEach(letter => {
        setTimeout(() => {
            const element = document.querySelector(`#logo-animated #${letter.id}`);
            if (element) {
                element.classList.add('animate-letter');
            }
        }, letter.delay);
    });
}

function startLogoAnimation() {
    // Vérifier d'abord si le SVG existe déjà
    const svg = document.querySelector('#logo-animated');
    
    if (svg) {
        startActualAnimation();
    } else {
        loadSVGInline();
    }
} 