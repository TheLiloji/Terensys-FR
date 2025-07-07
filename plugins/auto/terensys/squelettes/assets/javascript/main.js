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

