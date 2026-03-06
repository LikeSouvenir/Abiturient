let yandexMapInstance;
let placemarksCollection;
const linksDataForMap = JSON.parse(document.getElementById('map').dataset.links || '[]');

function initMap() {
    const mapElement = document.getElementById('map');
    const initialNoDataMsgMap = mapElement.querySelector('.php-map-message');

    if (!mapElement || linksDataForMap.length === 0) {
        if (mapElement && !initialNoDataMsgMap && linksDataForMap.length === 0) {
            mapElement.innerHTML = '<p class="no-results" style="text-align:center; padding-top: 40px;">Нет данных для отображения на карте.</p>';
        } else if (initialNoDataMsgMap) {
            initialNoDataMsgMap.style.display = 'block';
        }
        return;
    }
    if (initialNoDataMsgMap) initialNoDataMsgMap.style.display = 'none';

    try {
        yandexMapInstance = new ymaps.Map('map', {
            center: [59.9343, 30.3351],
            zoom: 10,
            controls: ['zoomControl', 'typeSelector', 'fullscreenControl', 'geolocationControl']
        });
        placemarksCollection = new ymaps.GeoObjectCollection({}, { preset: 'islands#blueDotIcon' });
        yandexMapInstance.geoObjects.add(placemarksCollection);

        const geocodingPromises = [];
        const allPlacemarksData = [];

        linksDataForMap.forEach((link) => {
            const collegeName = link.college_name || 'Учебное заведение';
            const programNameForBalloon = link.program_name_in_bundle || 'Программа';
            let balloonContent = `<h3>${programNameForBalloon}</h3>`;
            balloonContent += `<p><strong>Учебное заведение:</strong> ${collegeName}</p>`;
            if (link.education_type) balloonContent += `<p><strong>Образование:</strong> ${link.education_type}</p>`;
            if (link.base_level) balloonContent += `<p><strong>На базе:</strong> ${link.base_level}</p>`;
            if (link.duration) balloonContent += `<p><strong>Срок обучения:</strong> ${link.duration}</p>`;
            if (link.program_address) balloonContent += `<p><strong>Адрес проведения:</strong> ${link.program_address}</p>`;
            if (link.commission_address && link.commission_address !== link.program_address) balloonContent += `<p><strong>Адрес приемной комиссии:</strong> ${link.commission_address}</p>`;
            if (link.phone) balloonContent += `<p><strong>Телефон:</strong> ${link.phone}</p>`;
            if (link.website) balloonContent += `<p class="college-website"><strong>Сайт:</strong> <a href="${link.website}" target="_blank" rel="noopener noreferrer">${link.website}</a></p>`;

            let tagsHtml = "";
            if (link.program_attributes_array && link.program_attributes_array.length > 0) {
                link.program_attributes_array.forEach(attr => {
                    tagsHtml += `<span class="attribute-tag" style="background-color:#e7f3ff; color:#0056b3; padding:2px 5px; border-radius:3px; font-size:0.8em; margin-right:3px;">${attr}</span> `;
                });
            }
            if (link.is_professionalitet) {
                tagsHtml += `<span class="attribute-tag professionalitet-tag" style="background-color:#fff3cd; color:#856404; padding:2px 5px; border-radius:3px; font-size:0.8em; margin-right:3px;">Профессионалитет${link.cluster_name_on_card ? ': ' + link.cluster_name_on_card : ''}</span>`;
            }
            if (tagsHtml) balloonContent += `<div style="margin-top:5px;">${tagsHtml}</div>`;

            function addPlacemarkDataToList(coords, currentLinkData) {
                allPlacemarksData.push({
                    coords: coords,
                    id: currentLinkData.id,
                    properties: {
                        balloonContentHeader: programNameForBalloon,
                        balloonContentBody: balloonContent,
                        hintContent: programNameForBalloon + ' - ' + collegeName
                    },
                    options: {}
                });
            }

            if (link.latitude && link.longitude) {
                addPlacemarkDataToList([parseFloat(link.latitude), parseFloat(link.longitude)], link);
            } else if (link.map_address && link.map_address !== 'Адрес не указан') {
                const geocodePromise = ymaps.geocode(link.map_address, { results: 1 })
                    .then(function (res) {
                        const firstGeoObject = res.geoObjects.get(0);
                        if (firstGeoObject) {
                            addPlacemarkDataToList(firstGeoObject.geometry.getCoordinates(), link);
                        }
                    }, function(err) {
                        console.warn("Geocoding error for " + link.map_address + ":", err);
                        return Promise.resolve();
                    });
                geocodingPromises.push(geocodePromise);
            }
        });

        Promise.all(geocodingPromises)
            .then(() => {
                allPlacemarksData.forEach(data => {
                    const placemark = new ymaps.Placemark(data.coords, data.properties, data.options);
                    placemarksCollection.add(placemark);
                    const itemElement = document.querySelector(`.link-item[data-id="${data.id}"]`);
                    if (itemElement) {
                        itemElement.addEventListener('click', () => {
                            yandexMapInstance.setCenter(data.coords, 15, { checkZoomRange: true });
                            if (placemark.balloon.isOpen()) {
                                placemark.balloon.close();
                            } else {
                                placemark.balloon.open();
                            }
                        });
                    }
                });
                if (allPlacemarksData.length > 0) {
                    if (initialNoDataMsgMap) initialNoDataMsgMap.style.display = 'none';
                    yandexMapInstance.setBounds(placemarksCollection.getBounds(), { checkZoomRange: true, zoomMargin: 35 });
                } else {
                    const hasPotentiallyMappableData = linksDataForMap.some(link =>
                        (link.latitude && link.longitude) ||
                        (link.map_address && link.map_address !== 'Адрес не указан')
                    );
                    if (initialNoDataMsgMap) {
                        initialNoDataMsgMap.style.display = 'block';
                    } else if (hasPotentiallyMappableData) {
                        mapElement.innerHTML = '<p class="no-results" style="padding-top: 40px;">Не удалось определить координаты для отображения на карте.</p>';
                    } else if (linksDataForMap.length > 0 && !initialNoDataMsgMap) {
                        mapElement.innerHTML = '<p class="no-results" style="padding-top: 40px;">Данные есть, но нет информации для отображения на карте.</p>';
                    } else if (!initialNoDataMsgMap) {
                        mapElement.innerHTML = '<p class="no-results" style="padding-top: 40px;">Нет данных для отображения на карте.</p>';
                    }
                }
            })
            .catch(err => {
                console.error("Error processing placemarks:", err);
                if (initialNoDataMsgMap) initialNoDataMsgMap.style.display = 'none';
                mapElement.innerHTML = '<p class="no-results" style="padding-top: 40px;">Ошибка при обработке меток карты.</p>';
            });
    } catch (e) {
        console.error("Map init error:", e);
        if (initialNoDataMsgMap) initialNoDataMsgMap.style.display = 'none';
        mapElement.innerHTML = '<p class="no-results" style="padding-top: 40px;">Не удалось загрузить карту.</p>';
    }
}

function filterLinks(searchTerm) {
    const term = searchTerm.toLowerCase().trim();
    const items = document.querySelectorAll('#linkList .link-item');
    let visibleItemsCount = 0;
    items.forEach(item => {
        const text = item.dataset.searchText;
        const matches = !term || text.includes(term);
        item.style.display = matches ? 'flex' : 'none';
        if (matches) visibleItemsCount++;
    });
    const listContainer = document.getElementById('linkList');
    let jsMessage = listContainer.querySelector('.js-search-no-results');
    const phpMessage = listContainer.querySelector('.php-message');

    if (term && visibleItemsCount === 0 && items.length > 0) {
        if (!jsMessage) {
            jsMessage = document.createElement('p');
            jsMessage.className = 'no-results js-search-no-results';
            listContainer.appendChild(jsMessage);
        }
        jsMessage.textContent = 'По вашему запросу ничего не найдено.';
        jsMessage.style.display = 'block';
        if (phpMessage) phpMessage.style.display = 'none';
    } else {
        if (jsMessage) jsMessage.style.display = 'none';
        if (phpMessage && term === '') phpMessage.style.display = 'block';
        else if (phpMessage && term !== '') phpMessage.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof ymaps !== 'undefined') {
        ymaps.ready(initMap);
    } else {
        const mapElement = document.getElementById('map');
        const initialNoDataMsgMap = mapElement.querySelector('.php-map-message');
        if (initialNoDataMsgMap) initialNoDataMsgMap.style.display = 'none';
        if (mapElement) mapElement.innerHTML = '<p class="no-results" style="padding-top: 40px;">API Яндекс.Карт не загружено.</p>';
    }
    const searchInput = document.getElementById('linkSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => { filterLinks(e.target.value); });
        if (searchInput.value) { filterLinks(searchInput.value); }
    }
    const goHomeButtonEst = document.getElementById('goHomeButtonEst');
    if (goHomeButtonEst) {
        goHomeButtonEst.addEventListener('click', () => { window.location.href = 'index.php'; });
    }
    const backButton = document.getElementById('backButton');
    if (backButton) {
        backButton.addEventListener('click', () => { history.back(); });
    }
    const phpMessageInList = document.querySelector('#linkList > .php-message');
    if (linksDataForMap.length > 0 && phpMessageInList) {
        phpMessageInList.style.display = 'none';
    }
});