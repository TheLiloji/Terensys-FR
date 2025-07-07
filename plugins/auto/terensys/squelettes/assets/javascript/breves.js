document.querySelectorAll(".cards > li").forEach((el, index) => {
    el.style.setProperty("--index", index);
});

document.addEventListener("DOMContentLoaded", function () {
    const marquee = document.getElementById("brevesMarquee");
    const track = document.getElementById("brevesTrack");
    const content = document.getElementById("brevesContent");

    if (!content || content.children.length === 0) {
        return; // pas de brève, on n'anime rien
    }

    const originalContent = Array.from(track.children);

    // Duplication jusqu'à ce qu'on remplisse au moins 2x la largeur de la zone visible
    while (track.scrollWidth < marquee.offsetWidth * 2) {
        originalContent.forEach((el) => {
            const clone = el.cloneNode(true);
            track.appendChild(clone);
        });
    }

    let position = 0;
    const speed = 0.7; // pixels par frame

    function animate() {
        position -= speed;

        // Quand la 1re partie est complètement sortie du champ
        if (Math.abs(position) >= content.offsetWidth) {
            position = 0; // reset
        }

        track.style.transform = `translateX(${position}px)`;
        requestAnimationFrame(animate);
    }

    animate();
});