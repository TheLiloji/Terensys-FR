// navbar burger
const item = document.getElementById('item');
const submenu = document.getElementById('sub-menu');
const burger = document.getElementById('burger');
const menu = document.getElementById('burger-menu');
const menu_mobile = document.getElementById('burger-menu-mobile');
const icon = document.getElementById("menu-icon")

function getActiveMenu() {
    return window.matchMedia("(max-width: 1350px)").matches ? menu_mobile : menu;
}

burger.addEventListener('click', () => {
    const activeMenu = getActiveMenu();

    if (activeMenu.classList.contains("shown")) {
        activeMenu.classList.remove("shown");
        icon.innerText = "menu";
    } else {
        activeMenu.classList.add("shown");
        icon.innerText = "close";
    }
    console.log(activeMenu)
})

// item.addEventListener('mouseover', () => {
//     if (submenu.classList.contains("shown")) {
//         setTimeout(() => {
//             submenu.classList.remove("shown");
//         }, 1000)
//     } else {
//         submenu.classList.add("shown");
//     }
// })

window.addEventListener('resize', () => {
    menu.classList.remove("shown");
    menu_mobile.classList.remove("shown");
    icon.innerText = "menu";
    updateBurgerVisibility();
});

function updateBurgerVisibility() {
    const isMobile = window.matchMedia("(max-width: 1350px)").matches;
    const burger = document.getElementById('burger');

    if (isMobile) {
        burger.style.display = 'flex'; // forcer l'affichage en mobile
    } else {
        if (burger.getAttribute('style').includes('display: flex')) {
            burger.style.display = 'none';
        }
    }
}


window.addEventListener('DOMContentLoaded', updateBurgerVisibility);
