var map = L.map('map',{gestureHandling: true}).setView([47, 3], 6);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

map.addControl(L.control.search({ position: 'topright' }));


document.querySelectorAll(".clients").forEach(partenaire => {
    const adresses = partenaire.dataset.adresse.split('|').map(a => a.trim());

    adresses.forEach((adresse, index) => {
        const nom = partenaire.dataset.nom;
        const key = `geocode_${nom}_${index}`;
        const saved = localStorage.getItem(key);

        if(saved) {
            const {lat, lon} = JSON.parse(saved);
            addMarker(lat, lon, partenaire, adresse, nom);
        } else {
            map._geocoder.search(adresse).then(json => {
                const {lat, lon} = json[0];
                localStorage.setItem(key, JSON.stringify({lat, lon}))
                addMarker(lat, lon, partenaire, adresse, nom);
            })
        }
    });
})


function addMarker(lat, lon, partenaire, adresse, nom) {
    L.marker([lat, lon]).bindPopup(`
        <div class="popup_map">
            <p class="title">${nom}</p>
            <a href="${partenaire.dataset.url}" target="_blank">
                <img src="${partenaire.dataset.logo}" alt="${partenaire.dataset.nom}">
            </a>
            <p>${adresse}</p>
        </div>`
    ).addTo(map)
}