let yandexMapInstance;
let placemarksCollection;
let linksDataForMap = [];

// Получаем данные карты
try {
    const mapElement = document.getElementById('map');
    if (mapElement && mapElement.dataset.links) {
        linksDataForMap = JSON.parse(mapElement.dataset.links) || [];
    } else {
        linksDataForMap = [];
    }
} catch (e) {
    console.error("Error parsing map data:", e);
    linksDataForMap = [];
}

function initMap() {
    const mapElement = document.getElementById('map');
    
    // Проверяем наличие элемента карты
    if (!mapElement) {
        console.error("Map element not found");
        return;
    }
    
    const initialNoDataMsgMap = mapElement.querySelector('.php-map-message');

    // Если нет данных для карты
    if (linksDataForMap.length === 0) {
        if (mapElement && !initialNoDataMsgMap) {
            mapElement.innerHTML = '<p class="no-results" style="text-align:center; padding-top: 40px;">Нет данных для отображения на карте.</p>';
        } else if (initialNoDataMsgMap) {
            initialNoDataMsgMap.style.display = 'block';
        }
        return;
    }
    
    if (initialNoDataMsgMap) initialNoDataMsgMap.style.display = 'none';

    try {
        // Создаем карту с минимальным зумом
        yandexMapInstance = new ymaps.Map('map', {
            center: [55.7558, 37.6173], // Центр на Москву (можно изменить)
            zoom: 3, // Минимальный зум для обзора России/региона
            controls: ['zoomControl', 'typeSelector', 'fullscreenControl', 'geolocationControl']
        });
        
        // Ограничиваем максимальный зум
        yandexMapInstance.options.set('maxZoom', 17);
        
        // Создаем коллекцию для меток
        placemarksCollection = new ymaps.GeoObjectCollection({}, { 
            preset: 'islands#blueDotIcon' 
        });
        
        yandexMapInstance.geoObjects.add(placemarksCollection);

        const geocodingPromises = [];
        const allPlacemarksData = [];

        // Обрабатываем каждую программу
        linksDataForMap.forEach((link, index) => {
            // Добавляем уникальный ID если его нет
            if (!link.id) {
                link.id = 'program-' + index;
            }
            
            const collegeName = link.college_name || 'Учебное заведение';
            const programNameForBalloon = link.program_name_in_bundle || 'Программа';
            
            // Формируем содержимое балуна
            let balloonContent = `<div class="balloon-content">`;
            balloonContent += `<h3 style="margin:0 0 10px 0; color:#333;">${programNameForBalloon}</h3>`;
            balloonContent += `<p style="margin:5px 0;"><strong>Учебное заведение:</strong> ${collegeName}</p>`;
            
            if (link.education_type) {
                balloonContent += `<p style="margin:5px 0;"><strong>Образование:</strong> ${link.education_type}</p>`;
            }
            if (link.base_level) {
                balloonContent += `<p style="margin:5px 0;"><strong>На базе:</strong> ${link.base_level}</p>`;
            }
            if (link.duration) {
                balloonContent += `<p style="margin:5px 0;"><strong>Срок обучения:</strong> ${link.duration}</p>`;
            }
            if (link.program_address) {
                balloonContent += `<p style="margin:5px 0;"><strong>Адрес проведения:</strong> ${link.program_address}</p>`;
            }
            if (link.commission_address && link.commission_address !== link.program_address) {
                balloonContent += `<p style="margin:5px 0;"><strong>Адрес приемной комиссии:</strong> ${link.commission_address}</p>`;
            }
            if (link.phone) {
                balloonContent += `<p style="margin:5px 0;"><strong>Телефон:</strong> ${link.phone}</p>`;
            }
            if (link.website) {
                balloonContent += `<p style="margin:5px 0;" class="college-website"><strong>Сайт:</strong> <a href="${link.website}" target="_blank" rel="noopener noreferrer">${link.website}</a></p>`;
            }

            // Добавляем теги
            let tagsHtml = "";
            if (link.program_attributes_array && Array.isArray(link.program_attributes_array) && link.program_attributes_array.length > 0) {
                tagsHtml += '<div style="margin-top:10px;">';
                link.program_attributes_array.forEach(attr => {
                    tagsHtml += `<span style="display:inline-block; background-color:#e7f3ff; color:#0056b3; padding:2px 8px; border-radius:3px; font-size:0.85em; margin:0 3px 3px 0;">${attr}</span> `;
                });
                tagsHtml += '</div>';
            }
            
            if (link.is_professionalitet) {
                if (!tagsHtml) tagsHtml = '<div style="margin-top:10px;">';
                tagsHtml += `<span style="display:inline-block; background-color:#fff3cd; color:#856404; padding:2px 8px; border-radius:3px; font-size:0.85em; margin:0 3px 3px 0;">Профессионалитет${link.cluster_name_on_card ? ': ' + link.cluster_name_on_card : ''}</span>`;
                tagsHtml += '</div>';
            }
            
            if (tagsHtml) {
                balloonContent += tagsHtml;
            }
            
            balloonContent += '</div>';

            // Функция добавления метки
            function addPlacemarkDataToList(coords, currentLinkData) {
                allPlacemarksData.push({
                    coords: coords,
                    id: currentLinkData.id,
                    properties: {
                        balloonContentHeader: programNameForBalloon,
                        balloonContentBody: balloonContent,
                        hintContent: programNameForBalloon + ' - ' + collegeName
                    },
                    options: {
                        balloonCloseButton: true,
                        balloonMaxWidth: 300
                    }
                });
            }

            // Проверяем наличие координат
            if (link.latitude && link.longitude) {
                const lat = parseFloat(link.latitude);
                const lng = parseFloat(link.longitude);
                if (!isNaN(lat) && !isNaN(lng)) {
                    addPlacemarkDataToList([lat, lng], link);
                }
            } 
            // Если нет координат, но есть адрес - геокодируем
            else if (link.map_address && link.map_address !== 'Адрес не указан') {
                const geocodePromise = ymaps.geocode(link.map_address, { results: 1 })
                    .then(function (res) {
                        const firstGeoObject = res.geoObjects.get(0);
                        if (firstGeoObject) {
                            const coords = firstGeoObject.geometry.getCoordinates();
                            addPlacemarkDataToList(coords, link);
                        }
                    })
                    .catch(function(err) {
                        console.warn("Geocoding error for " + link.map_address + ":", err);
                    });
                geocodingPromises.push(geocodePromise);
            }
        });

        // Ждем завершения всех геокодирований
        Promise.all(geocodingPromises)
            .then(() => {
                // Добавляем все метки на карту
                allPlacemarksData.forEach(data => {
                    try {
                        const placemark = new ymaps.Placemark(data.coords, data.properties, data.options);
                        placemarksCollection.add(placemark);
                        
                        // Добавляем обработчик клика на элемент списка
                        const itemElement = document.querySelector(`.link-item[data-id="${data.id}"]`);
                        if (itemElement) {
                            itemElement.addEventListener('click', () => {
                                yandexMapInstance.setCenter(data.coords, 15, { checkZoomRange: true });
                                placemark.balloon.open();
                            });
                        }
                    } catch (e) {
                        console.error("Error creating placemark:", e);
                    }
                });
                
                // Настраиваем границы карты
                if (allPlacemarksData.length > 0) {
                    try {
                        // Получаем границы всех меток
                        const bounds = placemarksCollection.getBounds();
                        
                        if (bounds) {
                            // Проверяем разброс меток
                            const southWest = bounds[0];
                            const northEast = bounds[1];
                            const latDiff = Math.abs(northEast[0] - southWest[0]);
                            const lngDiff = Math.abs(northEast[1] - southWest[1]);
                            
                            // Если метки в одной точке или очень близко (разброс менее 0.5 градуса)
                            if (latDiff < 0.5 && lngDiff < 0.5) {
                                // Устанавливаем центр на первую метку с зумом 10
                                yandexMapInstance.setCenter(allPlacemarksData[0].coords, 10);
                            } else {
                                // Показываем все метки с отступами
                                yandexMapInstance.setBounds(bounds, { 
                                    checkZoomRange: true, 
                                    zoomMargin: 50,
                                    duration: 300
                                });
                            }
                        } else {
                            // Если не удалось получить границы, ставим центр на первую метку
                            yandexMapInstance.setCenter(allPlacemarksData[0].coords, 8);
                        }
                    } catch (e) {
                        console.error("Error setting bounds:", e);
                        // В случае ошибки ставим центр на первую метку
                        yandexMapInstance.setCenter(allPlacemarksData[0].coords, 8);
                    }
                } else {
                    // Если меток нет, но данные были - показываем сообщение
                    if (initialNoDataMsgMap) {
                        initialNoDataMsgMap.style.display = 'block';
                    } else {
                        mapElement.innerHTML = '<p class="no-results" style="text-align:center; padding-top: 40px;">Не удалось определить координаты для отображения на карте.</p>';
                    }
                }
            })
            .catch(err => {
                console.error("Error processing placemarks:", err);
                if (initialNoDataMsgMap) {
                    initialNoDataMsgMap.style.display = 'block';
                } else {
                    mapElement.innerHTML = '<p class="no-results" style="text-align:center; padding-top: 40px;">Ошибка при обработке меток карты.</p>';
                }
            });
    } catch (e) {
        console.error("Map init error:", e);
        if (initialNoDataMsgMap) {
            initialNoDataMsgMap.style.display = 'block';
        } else {
            mapElement.innerHTML = '<p class="no-results" style="text-align:center; padding-top: 40px;">Не удалось загрузить карту.</p>';
        }
    }
}

function filterLinks(searchTerm) {
    const term = searchTerm.toLowerCase().trim();
    const items = document.querySelectorAll('#linkList .link-item');
    let visibleItemsCount = 0;
    
    items.forEach(item => {
        const text = item.dataset.searchText;
        const matches = !term || (text && text.includes(term));
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
            jsMessage.style.textAlign = 'center';
            jsMessage.style.padding = '20px';
            listContainer.appendChild(jsMessage);
        }
        jsMessage.textContent = 'По вашему запросу ничего не найдено.';
        jsMessage.style.display = 'block';
        if (phpMessage) phpMessage.style.display = 'none';
    } else {
        if (jsMessage) jsMessage.style.display = 'none';
        if (phpMessage) {
            phpMessage.style.display = term === '' ? 'block' : 'none';
        }
    }
}

// Инициализация после загрузки DOM
document.addEventListener('DOMContentLoaded', () => {
    // Инициализация поиска
    const searchInput = document.getElementById('linkSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            filterLinks(e.target.value);
        });
        
        // Запускаем фильтрацию при загрузке, если есть значение
        if (searchInput.value) {
            filterLinks(searchInput.value);
        }
    }
    
    // Кнопка "На главную"
    const goHomeButtonEst = document.getElementById('goHomeButtonEst');
    if (goHomeButtonEst) {
        goHomeButtonEst.addEventListener('click', () => {
            window.location.href = 'index.php';
        });
    }
    
    // Кнопка "Назад"
    const backButton = document.getElementById('backButton');
    if (backButton) {
        backButton.addEventListener('click', () => {
            history.back();
        });
    }
    
    // Инициализация карты
    if (typeof ymaps !== 'undefined') {
        ymaps.ready(initMap);
    } else {
        console.warn("Yandex Maps API not loaded");
        const mapElement = document.getElementById('map');
        if (mapElement) {
            mapElement.innerHTML = '<p class="no-results" style="text-align:center; padding-top: 40px;">API Яндекс.Карт не загружено.</p>';
        }
    }
});