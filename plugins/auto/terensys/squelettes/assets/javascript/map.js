var map = L.map('map',{gestureHandling: true}).setView([47, 3], 6);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

map.addControl(L.control.search({ position: 'topright' }));

var markers = L.markerClusterGroup({
	spiderfyOnMaxZoom: false,
	showCoverageOnHover: false,
	zoomToBoundsOnClick: true
});

document.querySelectorAll(".clients").forEach(partenaire => {
    const adresse = partenaire.dataset.adresse;
    const nom = partenaire.dataset.nom;
    const key = `geocode_${nom}`;
    const saved = localStorage.getItem(key);

    if(saved) {
        const {lat, lon} = JSON.parse(saved);
        addMarker(lat, lon, partenaire)
    } else {
        map._geocoder.search(adresse).then(json => {
            const {lat, lon} = json[0];
            localStorage.setItem(key, JSON.stringify({lat, lon}))
            addMarker(lat, lon, partenaire)
        })
    }   
})

map.addLayer(markers);

function addMarker(lat, lon, partenaire) {
    markers.addLayer(L.marker([lat, lon]).bindPopup(`<div class="popup_map"><p class="title">${partenaire.dataset.nom}</p><a href="${partenaire.dataset.url}" target="_blank"><img src="${partenaire.dataset.logo}" alt="${partenaire.dataset.nom}"></a><p>${partenaire.dataset.adresse}</p></div>`))
}