let yandexMapInstance;
let placemarksCollection;
let linksDataForMap = [];
let mapInitialized = false;

function loadYandexMapsAPI() {
    return new Promise((resolve, reject) => {
        if (window.ymaps && window.ymaps.Map) {
            console.log('API Яндекс.Карт уже загружено');
            resolve();
            return;
        }

        const existingScript = document.querySelector('script[src*="api-maps.yandex.ru"]');
        if (existingScript) {
            console.log('Скрипт API уже загружается, ожидаем...');
            const checkInterval = setInterval(() => {
                if (window.ymaps && window.ymaps.Map) {
                    clearInterval(checkInterval);
                    console.log('API Яндекс.Карт загружено (ожидание)');
                    resolve();
                }
            }, 100);
            
            setTimeout(() => {
                clearInterval(checkInterval);
                reject(new Error('Timeout loading Yandex Maps API'));
            }, 10000);
            
            return;
        }

        console.log('Загружаем API Яндекс.Карт...');
        const script = document.createElement('script');
        // ВСТАВЬТЕ ВАШ РЕАЛЬНЫЙ API КЛЮЧ!
        script.src = 'https://api-maps.yandex.ru/2.1/?apikey=ВАШ_РЕАЛЬНЫЙ_КЛЮЧ_API&lang=ru_RU';
        script.type = 'text/javascript';
        
        script.onload = () => {
            console.log('Скрипт API загружен, ожидаем ymaps...');
            const checkYmaps = setInterval(() => {
                if (window.ymaps && window.ymaps.Map) {
                    clearInterval(checkYmaps);
                    console.log('ymaps готов к использованию');
                    resolve();
                }
            }, 50);
            
            setTimeout(() => {
                clearInterval(checkYmaps);
                reject(new Error('Timeout waiting for ymaps'));
            }, 5000);
        };
        
        script.onerror = () => reject(new Error('Failed to load Yandex Maps API'));
        
        document.head.appendChild(script);
    });
}

// Функция для извлечения данных из разных источников
function getMapData() {
    // 1. Проверяем window.mapData (из map_template.php)
    if (window.mapData && Array.isArray(window.mapData) && window.mapData.length > 0) {
        console.log('Данные получены из window.mapData');
        return window.mapData;
    }
    
    // 2. Проверяем dataset.links (из establishments.php)
    const mapElement = document.getElementById('map');
    if (mapElement && mapElement.dataset.links) {
        try {
            const data = JSON.parse(mapElement.dataset.links);
            if (data && Array.isArray(data) && data.length > 0) {
                console.log('Данные получены из dataset.links');
                return data;
            }
        } catch(e) {
            console.error('Ошибка парсинга dataset.links:', e);
        }
    }
    
    // 3. Проверяем глобальную переменную linksData (на всякий случай)
    if (window.linksDataForMap && Array.isArray(window.linksDataForMap) && window.linksDataForMap.length > 0) {
        console.log('Данные получены из linksDataForMap');
        return window.linksDataForMap;
    }
    
    return null;
}

// Функция для проверки наличия координат в данных
function hasCoordinatesInData(data) {
    if (!data || !Array.isArray(data)) return false;
    
    for (const item of data) {
        // Проверяем map_points
        if (item.map_points && Array.isArray(item.map_points)) {
            for (const point of item.map_points) {
                if (point.latitude && point.longitude && point.latitude !== null && point.longitude !== null) {
                    return true;
                }
            }
        }
        // Проверяем прямые координаты
        else if (item.latitude && item.longitude && item.latitude !== null && item.longitude !== null) {
            return true;
        }
    }
    return false;
}

// Функция для отображения всех меток на карте
function initMap() {
    console.log('initMap вызван, mapInitialized =', mapInitialized);
    
    if (mapInitialized) {
        console.log('Карта уже инициализирована');
        return;
    }
    
    if (typeof ymaps === 'undefined' || !ymaps.Map) {
        console.error('Яндекс.Карты не загружены');
        return;
    }
    
    // Получаем данные
    const linksData = getMapData();
    
    if (!linksData || linksData.length === 0) {
        console.error('Нет данных для карты');
        const mapDiv = document.getElementById('map');
        if (mapDiv) {
            mapDiv.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">📭 Нет данных для отображения на карте</div>';
        }
        return;
    }
    
    console.log('Получено данных:', linksData.length, 'записей');
    console.log('Пример первой записи:', linksData[0]);
    
    // Проверяем существование элемента map
    const mapElement = document.getElementById('map');
    if (!mapElement) {
        console.error('Элемент #map не найден');
        return;
    }
    
    // Собираем уникальные точки
    let uniquePoints = new Map();
    
    linksData.forEach((item, idx) => {
        console.log(`Обработка элемента ${idx}:`, item.college_name || item.name || 'Без названия');
        
        // Проверяем наличие map_points
        if (item.map_points && Array.isArray(item.map_points) && item.map_points.length > 0) {
            item.map_points.forEach(point => {
                if (point.latitude && point.longitude) {
                    const lat = parseFloat(point.latitude);
                    const lon = parseFloat(point.longitude);
                    const address = point.address;
                    
                    console.log(`  Точка из map_points: ${lat}, ${lon} - ${address}`);
                    
                    if (!isNaN(lat) && !isNaN(lon) && lat !== 0 && lon !== 0) {
                        const key = `${lat},${lon}`;
                        
                        if (!uniquePoints.has(key)) {
                            uniquePoints.set(key, {
                                id: item.id || item.program_id || idx,
                                name: item.college_name || item.name,
                                program: item.program_name_in_bundle,
                                address: address,
                                latitude: lat,
                                longitude: lon,
                                type: point.type || 'regular'
                            });
                            console.log(`    ✅ Добавлена уникальная точка`);
                        } else {
                            console.log(`    ⏭️ Точка уже существует`);
                        }
                    }
                }
            });
        } 
        // Проверяем наличие координат напрямую в объекте
        else if (item.latitude && item.longitude && item.latitude !== null && item.longitude !== null) {
            const lat = parseFloat(item.latitude);
            const lon = parseFloat(item.longitude);
            const address = item.map_address || item.program_address || item.address;
            
            console.log(`  Точка напрямую: ${lat}, ${lon} - ${address}`);
            
            if (!isNaN(lat) && !isNaN(lon) && lat !== 0 && lon !== 0) {
                const key = `${lat},${lon}`;
                
                if (!uniquePoints.has(key)) {
                    uniquePoints.set(key, {
                        id: item.id || item.program_id || idx,
                        name: item.college_name || item.name,
                        program: item.program_name_in_bundle,
                        address: address,
                        latitude: lat,
                        longitude: lon,
                        type: 'regular'
                    });
                    console.log(`    ✅ Добавлена уникальная точка`);
                }
            }
        } else {
            console.log(`  ⚠️ Нет координат для элемента ${idx}`);
        }
    });
    
    const allPoints = Array.from(uniquePoints.values());
    console.log(`Всего уникальных точек: ${allPoints.length}`);
    
    if (allPoints.length === 0) {
        console.log('Нет точек с координатами');
        if (mapElement) {
            mapElement.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">📍 Нет адресов с координатами для отображения</div>';
        }
        return;
    }
    
    // Инициализируем карту
    mapInitialized = true;
    
    // Функция для создания и добавления меток
    const createPlacemarks = () => {
        console.log('Начинаем добавление меток...');
        
        // Цвета меток
        const getPreset = (type) => {
            switch(type) {
                case 'admission':
                    return 'islands#redEducationIcon';
                case 'regular':
                    return 'islands#blueEducationIcon';
                case 'program':
                    return 'islands#greenEducationIcon';
                default:
                    return 'islands#blueEducationIcon';
            }
        };
        
        const getTypeName = (type) => {
            switch(type) {
                case 'admission':
                    return 'Приемная комиссия';
                case 'regular':
                    return 'Основной адрес';
                case 'program':
                    return 'Адрес программы';
                default:
                    return 'Адрес';
            }
        };
        
        // Создаем метки
        allPoints.forEach((point, index) => {
            console.log(`Создаем метку ${index + 1} для: ${point.address}`);
            
            const placemark = new ymaps.Placemark(
                [point.latitude, point.longitude],
                {
                    balloonContentHeader: `<strong>${escapeHtml(point.name)}</strong>`,
                    balloonContentBody: `
                        ${point.program ? `<strong>Программа:</strong> ${escapeHtml(point.program)}<br>` : ''}
                        <strong>Адрес:</strong> ${escapeHtml(point.address)}<br>
                        <strong>Тип:</strong> ${getTypeName(point.type)}<br><br>
                        <a href="#" onclick="scrollToCard(${point.id}); return false;" style="display: inline-block; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 3px;">📋 Подробнее о программе</a>
                    `,
                    balloonContentFooter: '🏫 Учебное заведение'
                },
                {
                    preset: getPreset(point.type),
                    balloonCloseButton: true,
                    openBalloonOnClick: true
                }
            );
            
            // Добавляем обработчик клика
            placemark.events.add('click', function() {
                scrollToCard(point.id);
            });
            
            // Добавляем метку на карту
            yandexMapInstance.geoObjects.add(placemark);
        });
        
        console.log(`✅ Добавлено ${allPoints.length} меток на карту`);
        
        // Устанавливаем границы карты
        if (allPoints.length > 1) {
            try {
                const bounds = yandexMapInstance.geoObjects.getBounds();
                if (bounds) {
                    yandexMapInstance.setBounds(bounds, {
                        checkZoomRange: true,
                        zoomMargin: 50
                    });
                }
            } catch(e) {
                console.warn('Ошибка установки границ:', e);
            }
        } else if (allPoints.length === 1) {
            // Если одна метка, просто центрируем на ней
            yandexMapInstance.setCenter([allPoints[0].latitude, allPoints[0].longitude], 15);
        }
    };
    
    try {
        // Создаем карту
        yandexMapInstance = new ymaps.Map('map', {
            center: [allPoints[0].latitude, allPoints[0].longitude],
            zoom: 14,
            controls: ['zoomControl', 'fullscreenControl']
        });
        
        console.log('Карта создана, ожидаем готовности...');
        
        // Добавляем метки после небольшой задержки
        setTimeout(() => {
            createPlacemarks();
        }, 200);
        
    } catch(e) {
        console.error('Ошибка при создании карты:', e);
        mapInitialized = false;
        const mapDiv = document.getElementById('map');
        if (mapDiv) {
            mapDiv.innerHTML = '<div style="padding: 20px; text-align: center; color: red;">❌ Ошибка создания карты: ' + e.message + '</div>';
        }
    }
}

// Функция для экранирования HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Функция прокрутки к карточке
function scrollToCard(cardId) {
    console.log('Прокрутка к карточке:', cardId);
    const card = document.querySelector(`.link-item[data-id="${cardId}"]`);
    if (card) {
        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        card.classList.add('highlight-card');
        setTimeout(() => {
            card.classList.remove('highlight-card');
        }, 2000);
    } else {
        console.warn('Карточка не найдена:', cardId);
    }
}

// Добавляем стили для подсветки карточки
const style = document.createElement('style');
style.textContent = `
    .highlight-card {
        background-color: #fff3cd !important;
        border: 2px solid #ffc107 !important;
        transition: all 0.3s ease;
        box-shadow: 0 0 10px rgba(255, 193, 7, 0.5) !important;
        border-radius: 8px;
    }
`;
document.head.appendChild(style);

// Инициализация после загрузки DOM
document.addEventListener('DOMContentLoaded', async () => {
    console.log('DOM загружен, начинаем инициализацию карты...');
    
    // Инициализация поиска
    const searchInput = document.getElementById('linkSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            filterLinks(e.target.value);
        });
        
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
    
    // Проверяем данные для карты
    const mapElement = document.getElementById('map');
    if (!mapElement) {
        console.error('Элемент #map не найден');
        return;
    }
    
    // Получаем данные из любого источника
    const mapData = getMapData();
    
    if (!mapData || mapData.length === 0) {
        console.log('Нет данных для отображения на карте');
        mapElement.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">📭 Нет данных для отображения на карте</div>';
        return;
    }
    
    // Проверяем наличие координат
    if (!hasCoordinatesInData(mapData)) {
        console.log('Нет координат для отображения на карте');
        mapElement.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">📍 Нет координат для отображения на карте</div>';
        return;
    }
    
    // Загружаем API и инициализируем карту
    try {
        await loadYandexMapsAPI();
        console.log('API загружено, вызываем ymaps.ready');
        ymaps.ready(initMap);
    } catch (error) {
        console.error("Failed to load Yandex Maps API:", error);
        if (mapElement) {
            mapElement.innerHTML = '<div style="padding: 20px; text-align: center; color: red;">❌ Не удалось загрузить API Яндекс.Карт. Проверьте API ключ и подключение к интернету.</div>';
        }
    }
});

// Функция фильтрации
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