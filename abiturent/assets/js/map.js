let yandexMapInstance;
let placemarksCollection;
let linksDataForMap = [];
let mapInitialized = false;

function loadYandexMapsAPI() {
    return new Promise((resolve, reject) => {
        if (window.ymaps && window.ymaps.Map) {
            console.log('✅ API Яндекс.Карт уже загружено');
            resolve();
            return;
        }

        const existingScript = document.querySelector('script[src*="api-maps.yandex.ru"]');
        if (existingScript) {
            console.log('⏳ Скрипт API уже загружается, ожидаем...');
            const checkInterval = setInterval(() => {
                if (window.ymaps && window.ymaps.Map) {
                    clearInterval(checkInterval);
                    console.log('✅ API Яндекс.Карт загружено (ожидание)');
                    resolve();
                }
            }, 100);
            
            setTimeout(() => {
                clearInterval(checkInterval);
                reject(new Error('Timeout loading Yandex Maps API'));
            }, 10000);
            
            return;
        }

        console.log('📥 Загружаем API Яндекс.Карт...');
        const script = document.createElement('script');
        // 🔑 ВАЖНО: Замените на ваш реальный API ключ!
        const API_KEY = 'ВАШ_РЕАЛЬНЫЙ_API_КЛЮЧ_ОТ_ЯНДЕКС_КАРТ';
        script.src = `https://api-maps.yandex.ru/2.1/?apikey=${API_KEY}&lang=ru_RU`;
        script.type = 'text/javascript';
        
        script.onload = () => {
            console.log('✅ Скрипт API загружен, ожидаем ymaps...');
            const checkYmaps = setInterval(() => {
                if (window.ymaps && window.ymaps.Map) {
                    clearInterval(checkYmaps);
                    console.log('✅ ymaps готов к использованию');
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

// Функция для извлечения данных с ожиданием
function getMapData() {
    // 1. Проверяем window.mapData
    if (window.mapData && Array.isArray(window.mapData) && window.mapData.length > 0) {
        console.log('✅ Данные получены из window.mapData, количество:', window.mapData.length);
        console.log('📋 Пример данных:', window.mapData[0]);
        return window.mapData;
    }
    
    // 2. Проверяем dataset.links
    const mapElement = document.getElementById('map');
    if (mapElement && mapElement.dataset.links) {
        try {
            const data = JSON.parse(mapElement.dataset.links);
            if (data && Array.isArray(data) && data.length > 0) {
                console.log('✅ Данные получены из dataset.links, количество:', data.length);
                return data;
            }
        } catch(e) {
            console.error('❌ Ошибка парсинга dataset.links:', e);
        }
    }
    
    // 3. Проверяем глобальную переменную linksDataForMap
    if (window.linksDataForMap && Array.isArray(window.linksDataForMap) && window.linksDataForMap.length > 0) {
        console.log('✅ Данные получены из linksDataForMap, количество:', window.linksDataForMap.length);
        return window.linksDataForMap;
    }
    
    // 4. Пробуем найти данные в переменной mapData (без window)
    if (typeof mapData !== 'undefined' && Array.isArray(mapData) && mapData.length > 0) {
        console.log('✅ Данные получены из mapData (глобальная), количество:', mapData.length);
        window.mapData = mapData; // Сохраняем в window для будущего использования
        return mapData;
    }
    
    console.warn('⚠️ Данные для карты не найдены ни в одном источнике');
    console.log('Доступные глобальные переменные:', Object.keys(window).filter(k => k.includes('map') || k.includes('data')));
    return null;
}

// Модифицируем инициализацию - ждём появления данных
document.addEventListener('DOMContentLoaded', async () => {
    console.log('🚀 DOM загружен, начинаем инициализацию...');
    
    // ... остальной код инициализации ...
    
    // Ждём появления данных (максимум 2 секунды)
    let mapData = null;
    let attempts = 0;
    const maxAttempts = 20;
    
    while (!mapData && attempts < maxAttempts) {
        mapData = getMapData();
        if (!mapData) {
            await new Promise(resolve => setTimeout(resolve, 100));
            attempts++;
        }
    }
    
    if (!mapData || mapData.length === 0) {
        console.log('📭 Данные для карты не найдены после ожидания');
        const mapElement = document.getElementById('map');
        if (mapElement) {
            mapElement.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">📭 Нет данных для отображения на карте</div>';
        }
        return;
    }
    
    // ... остальной код загрузки карты ...
});

// 🔧 ИСПРАВЛЕННАЯ ФУНКЦИЯ: Корректно преобразует строки в числа
function normalizeCoordinate(coord) {
    // Если координата null или undefined
    if (coord === null || coord === undefined || coord === '') {
        return NaN;
    }
    
    // Если это строка - очищаем и преобразуем
    if (typeof coord === 'string') {
        // Удаляем пробелы в начале и конце
        coord = coord.trim();
        // Заменяем запятую на точку (на случай если пришло "59,93428")
        coord = coord.replace(',', '.');
        // Удаляем все символы кроме цифр, точки и минуса
        coord = coord.replace(/[^\d.-]/g, '');
    }
    
    // Пробуем преобразовать в число
    const parsed = parseFloat(coord);
    
    // Проверяем, что получилось число и оно не NaN
    if (!isNaN(parsed) && isFinite(parsed) && parsed !== 0) {
        return parsed;
    }
    
    return NaN;
}

// Функция для проверки наличия координат в данных
function hasCoordinatesInData(data) {
    if (!data || !Array.isArray(data)) return false;
    
    for (const item of data) {
        // Проверяем map_points
        if (item.map_points && Array.isArray(item.map_points)) {
            for (const point of item.map_points) {
                const lat = normalizeCoordinate(point.latitude);
                const lon = normalizeCoordinate(point.longitude);
                if (!isNaN(lat) && !isNaN(lon)) {
                    return true;
                }
            }
        }
        // Проверяем прямые координаты
        else if (item.latitude && item.longitude) {
            const lat = normalizeCoordinate(item.latitude);
            const lon = normalizeCoordinate(item.longitude);
            if (!isNaN(lat) && !isNaN(lon)) {
                return true;
            }
        }
    }
    return false;
}

// Функция для отображения всех меток на карте
function initMap() {
    console.log('🗺️ initMap вызван, mapInitialized =', mapInitialized);
    
    if (mapInitialized) {
        console.log('⚠️ Карта уже инициализирована');
        return;
    }
    
    if (typeof ymaps === 'undefined' || !ymaps.Map) {
        console.error('❌ Яндекс.Карты не загружены');
        return;
    }
    
    // Получаем данные
    const linksData = getMapData();
    
    if (!linksData || linksData.length === 0) {
        console.error('❌ Нет данных для карты');
        const mapDiv = document.getElementById('map');
        if (mapDiv) {
            mapDiv.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">📭 Нет данных для отображения на карте</div>';
        }
        return;
    }
    
    console.log('📊 Получено данных:', linksData.length, 'записей');
    
    // Проверяем существование элемента map
    const mapElement = document.getElementById('map');
    if (!mapElement) {
        console.error('❌ Элемент #map не найден');
        return;
    }
    
    // Собираем уникальные точки
    let uniquePoints = new Map();
    let totalPointsChecked = 0;
    let validPointsFound = 0;
    let invalidPointsCount = 0;
    
    linksData.forEach((item, idx) => {
        console.log(`\n🔍 Обработка элемента ${idx}:`);
        console.log(`  Название: ${item.college_name || item.name || 'Без названия'}`);
        console.log(`  latitude (сырое): "${item.latitude}" (тип: ${typeof item.latitude})`);
        console.log(`  longitude (сырое): "${item.longitude}" (тип: ${typeof item.longitude})`);
        
        // Проверяем наличие map_points
        if (item.map_points && Array.isArray(item.map_points) && item.map_points.length > 0) {
            console.log(`  📍 Найдено map_points: ${item.map_points.length}`);
            
            item.map_points.forEach((point, pointIdx) => {
                totalPointsChecked++;
                
                const lat = normalizeCoordinate(point.latitude);
                const lon = normalizeCoordinate(point.longitude);
                const address = point.address || 'Адрес не указан';
                
                console.log(`    Точка ${pointIdx}: lat="${point.latitude}" → ${lat}, lon="${point.longitude}" → ${lon}`);
                
                if (!isNaN(lat) && !isNaN(lon)) {
                    const key = `${lat.toFixed(6)},${lon.toFixed(6)}`;
                    
                    if (!uniquePoints.has(key)) {
                        validPointsFound++;
                        uniquePoints.set(key, {
                            id: item.id || item.program_id || idx,
                            name: item.college_name || item.name || 'Без названия',
                            program: item.program_name_in_bundle || '',
                            address: address,
                            latitude: lat,
                            longitude: lon,
                            type: point.type || 'regular'
                        });
                        console.log(`      ✅ Точка добавлена: ${address}`);
                    } else {
                        console.log(`      ⏭️ Точка уже существует`);
                    }
                } else {
                    invalidPointsCount++;
                    console.warn(`      ❌ Некорректные координаты после преобразования`);
                }
            });
        } 
        // Проверяем наличие координат напрямую в объекте
        else {
            totalPointsChecked++;
            
            const lat = normalizeCoordinate(item.latitude);
            const lon = normalizeCoordinate(item.longitude);
            const address = item.map_address || item.program_address || item.address || 'Адрес не указан';
            
            console.log(`  📍 Преобразованные координаты: lat=${lat}, lon=${lon}`);
            
            if (!isNaN(lat) && !isNaN(lon)) {
                const key = `${lat.toFixed(6)},${lon.toFixed(6)}`;
                
                if (!uniquePoints.has(key)) {
                    validPointsFound++;
                    uniquePoints.set(key, {
                        id: item.id || item.program_id || idx,
                        name: item.college_name || item.name || 'Без названия',
                        program: item.program_name_in_bundle || '',
                        address: address,
                        latitude: lat,
                        longitude: lon,
                        type: 'regular'
                    });
                    console.log(`    ✅ Точка добавлена: ${address}`);
                } else {
                    console.log(`    ⏭️ Точка уже существует`);
                }
            } else {
                invalidPointsCount++;
                console.warn(`    ❌ Некорректные координаты: lat="${item.latitude}", lon="${item.longitude}"`);
            }
        }
    });
    
    const allPoints = Array.from(uniquePoints.values());
    
    console.log('\n📈 ИТОГОВАЯ СТАТИСТИКА:');
    console.log(`  Всего проверено элементов: ${totalPointsChecked}`);
    console.log(`  Валидных точек: ${validPointsFound}`);
    console.log(`  Уникальных точек: ${allPoints.length}`);
    console.log(`  Некорректных точек: ${invalidPointsCount}`);
    
    if (allPoints.length === 0) {
        console.log('❌ Нет точек с валидными координатами');
        if (mapElement) {
            mapElement.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">📍 Нет адресов с корректными координатами для отображения</div>';
        }
        return;
    }
    
    // Инициализируем карту
    mapInitialized = true;
    
    try {
        // Вычисляем центр карты
        let centerLat = 59.93428; // Центр СПб по умолчанию
        let centerLon = 30.3351;
        
        if (allPoints.length > 0) {
            // Находим среднее значение координат
            const sumLat = allPoints.reduce((sum, p) => sum + p.latitude, 0);
            const sumLon = allPoints.reduce((sum, p) => sum + p.longitude, 0);
            centerLat = sumLat / allPoints.length;
            centerLon = sumLon / allPoints.length;
        }
        
        // Создаем карту
        yandexMapInstance = new ymaps.Map('map', {
            center: [centerLat, centerLon],
            zoom: 11,
            controls: ['zoomControl', 'fullscreenControl']
        });
        
        console.log('✅ Карта создана');
        
        // Добавляем метки
        addPlacemarksToMap(allPoints);
        
    } catch(e) {
        console.error('❌ Ошибка при создании карты:', e);
        mapInitialized = false;
        const mapDiv = document.getElementById('map');
        if (mapDiv) {
            mapDiv.innerHTML = '<div style="padding: 20px; text-align: center; color: red;">❌ Ошибка создания карты: ' + e.message + '</div>';
        }
    }
}

// Функция для добавления меток на карту
function addPlacemarksToMap(points) {
    if (!yandexMapInstance) {
        console.error('❌ Карта не инициализирована');
        return;
    }
    
    console.log(`🗺️ Добавляем ${points.length} меток на карту...`);
    
    // Очищаем существующие метки
    yandexMapInstance.geoObjects.removeAll();
    
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
    points.forEach((point, index) => {
        try {
            console.log(`  📍 Метка ${index + 1}/${points.length}: ${point.address} (${point.latitude}, ${point.longitude})`);
            
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
            
        } catch(e) {
            console.error(`    ❌ Ошибка:`, e);
        }
    });
    
    console.log(`✅ Добавлено меток: ${yandexMapInstance.geoObjects.getLength()}`);
    
    // Устанавливаем границы карты
    if (points.length > 1) {
        try {
            const bounds = yandexMapInstance.geoObjects.getBounds();
            if (bounds && bounds[0] && bounds[1]) {
                yandexMapInstance.setBounds(bounds, {
                    checkZoomRange: true,
                    zoomMargin: 50
                });
            }
        } catch(e) {
            console.warn('⚠️ Ошибка установки границ:', e);
            yandexMapInstance.setCenter([points[0].latitude, points[0].longitude], 12);
        }
    } else if (points.length === 1) {
        yandexMapInstance.setCenter([points[0].latitude, points[0].longitude], 15);
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
    console.log('🔍 Прокрутка к карточке:', cardId);
    const card = document.querySelector(`.link-item[data-id="${cardId}"]`);
    if (card) {
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        card.classList.add('highlight-card');
        setTimeout(() => {
            card.classList.remove('highlight-card');
        }, 2000);
    } else {
        console.warn('⚠️ Карточка не найдена:', cardId);
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

// Делаем функцию scrollToCard глобальной
window.scrollToCard = scrollToCard;

// Инициализация после загрузки DOM
document.addEventListener('DOMContentLoaded', async () => {
    console.log('🚀 DOM загружен, начинаем инициализацию...');
    
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
        console.error('❌ Элемент #map не найден');
        return;
    }
    
    // Получаем данные из любого источника
    const mapData = getMapData();
    
    if (!mapData || mapData.length === 0) {
        console.log('📭 Нет данных для отображения на карте');
        mapElement.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">📭 Нет данных для отображения на карте</div>';
        return;
    }
    
    // Проверяем наличие координат
    if (!hasCoordinatesInData(mapData)) {
        console.log('📍 Нет корректных координат для отображения на карте');
        mapElement.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">📍 Нет корректных координат для отображения на карте</div>';
        return;
    }
    
    // Загружаем API и инициализируем карту
    try {
        await loadYandexMapsAPI();
        console.log('✅ API загружено, вызываем ymaps.ready');
        ymaps.ready(initMap);
    } catch (error) {
        console.error("❌ Failed to load Yandex Maps API:", error);
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