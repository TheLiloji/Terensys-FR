// text appear on scoll effect
function checkForVisibility() {
    const headers = document.querySelectorAll(".header");
    headers.forEach(function (header) {
        if (isElementInViewport(header)) {
            header.classList.add("headerVisible");
        } else {
            header.classList.remove("headerVisible");
        }
    });
}

function isElementInViewport(el) {
    const rect = el.getBoundingClientRect();

    return (
        rect.top >= -256 &&
        rect.left >= 0 &&
        rect.bottom <=
            (window.innerHeight || document.documentElement.clientHeight) + 198 &&
        rect.right <=
            (window.innerWidth || document.documentElement.clientWidth)
    );
}

if (window.addEventListener) {
    addEventListener("DOMContentLoaded", checkForVisibility, false);
    addEventListener("load", checkForVisibility, false);
    addEventListener("scroll", checkForVisibility, false);
}


document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.cards > li');

    let activeCard = null;

    cards.forEach(card => {
        card.addEventListener('touchstart', function (e) {
            if (activeCard && activeCard !== card) {
                activeCard.classList.remove('tapped');
                activeCard = null;
            }

            if (!card.classList.contains('tapped')) {
                card.classList.add('tapped');
                activeCard = card;
                e.preventDefault(); // empêche le lien de se déclencher immédiatement
            } else {
                const link = card.querySelector('a');
                if (link) link.click();
            }
        });
    });

    // Clic ailleurs = désélection
    document.addEventListener('touchstart', function (e) {
        if (
            activeCard &&
            !e.target.closest('.cards > li')
        ) {
            activeCard.classList.remove('tapped');
            activeCard = null;
        }
    });
});