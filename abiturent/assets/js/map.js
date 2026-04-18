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
        // 🔑 ВАЖНО: Замените на ваш реальный API ключ от Яндекс.Карт
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
    console.log('🔍 getMapData: начинаем поиск данных...');
    
    // 1. Проверяем window.mapData
    if (window.mapData && Array.isArray(window.mapData)) {
        if (window.mapData.length > 0) {
            console.log('✅ Данные получены из window.mapData, количество:', window.mapData.length);
            console.log('📋 Пример данных:', window.mapData[0]);
            return window.mapData;
        } else {
            console.log('⚠️ window.mapData существует, но пустой массив');
        }
    }
    
    // 2. Проверяем dataset.links на элементе map
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
    
    console.warn('⚠️ Данные для карты не найдены ни в одном источнике');
    console.log('Доступные глобальные переменные:', Object.keys(window).filter(k => 
        k.includes('map') || k.includes('data') || k.includes('links') || k.includes('programs')
    ));
    
    return null;
}

// Функция для преобразования координат
function normalizeCoordinate(coord) {
    if (coord === null || coord === undefined || coord === '') {
        return NaN;
    }
    
    if (typeof coord === 'string') {
        coord = coord.trim();
        coord = coord.replace(',', '.');
        coord = coord.replace(/[^\d.-]/g, '');
    }
    
    const parsed = parseFloat(coord);
    
    if (!isNaN(parsed) && isFinite(parsed) && parsed !== 0) {
        return parsed;
    }
    
    return NaN;
}

// Функция для проверки наличия координат в данных
function hasCoordinatesInData(data) {
    if (!data || !Array.isArray(data)) return false;
    
    for (const item of data) {
        if (item.map_points && Array.isArray(item.map_points)) {
            for (const point of item.map_points) {
                const lat = normalizeCoordinate(point.latitude);
                const lon = normalizeCoordinate(point.longitude);
                if (!isNaN(lat) && !isNaN(lon)) {
                    return true;
                }
            }
        }
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

// Функция для добавления меток на карту
function addPlacemarksToMap(points) {
    if (!yandexMapInstance) {
        console.error('❌ Карта не инициализирована');
        return;
    }
    
    console.log(`🗺️ Добавляем ${points.length} меток на карту...`);
    
    // Очищаем существующие метки
    yandexMapInstance.geoObjects.removeAll();
    
    // Цвета меток для разных типов
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
    
    // Группируем точки по адресу для отображения количества программ
    const addressMap = new Map();
    points.forEach(point => {
        const key = `${point.latitude.toFixed(6)},${point.longitude.toFixed(6)}`;
        if (!addressMap.has(key)) {
            addressMap.set(key, {
                ...point,
                programs_count: 1,
                programs_list: [point.program]
            });
        } else {
            const existing = addressMap.get(key);
            existing.programs_count++;
            if (point.program && !existing.programs_list.includes(point.program)) {
                existing.programs_list.push(point.program);
            }
        }
    });
    
    const uniquePoints = Array.from(addressMap.values());
    console.log(`📊 Уникальных адресов после группировки: ${uniquePoints.length}`);
    
    // Создаем метки для уникальных точек
    uniquePoints.forEach((point, index) => {
        try {
            console.log(`  📍 Метка ${index + 1}/${uniquePoints.length}: ${point.address} (${point.latitude}, ${point.longitude})`);
            
            // Формируем содержимое балуна
            let balloonContent = `
                <div style="max-width: 350px;">
                    <strong style="font-size: 16px;">${escapeHtml(point.name)}</strong><br>
                    <hr style="margin: 8px 0;">
                    <strong>📍 Адрес:</strong> ${escapeHtml(point.address)}<br>
                    <strong>🏷️ Тип:</strong> ${getTypeName(point.type)}<br>
            `;
            
            if (point.programs_count > 1) {
                balloonContent += `<strong>📚 Программ по адресу:</strong> ${point.programs_count}<br>`;
                balloonContent += `<details style="margin-top: 8px;">
                    <summary style="cursor: pointer; color: #007bff;">Показать программы (${point.programs_list.length})</summary>
                    <ul style="margin-top: 8px; padding-left: 20px;">`;
                point.programs_list.forEach(prog => {
                    if (prog) {
                        balloonContent += `<li>${escapeHtml(prog)}</li>`;
                    }
                });
                balloonContent += `</ul></details>`;
            } else if (point.program) {
                balloonContent += `<strong>📖 Программа:</strong> ${escapeHtml(point.program)}<br>`;
            }
            
            balloonContent += `
                    <br>
                    <button onclick="scrollToCard(${point.id})" style="padding: 8px 12px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
                        📋 Подробнее о программе
                    </button>
                </div>
            `;
            
            const placemark = new ymaps.Placemark(
                [point.latitude, point.longitude],
                {
                    balloonContentHeader: `<strong>${escapeHtml(point.name)}</strong>`,
                    balloonContentBody: balloonContent,
                    balloonContentFooter: '🏫 Учебное заведение'
                },
                {
                    preset: getPreset(point.type),
                    balloonCloseButton: true,
                    openBalloonOnClick: true,
                    iconColor: point.type === 'admission' ? '#FF0000' : (point.type === 'program' ? '#00AA00' : '#0066CC')
                }
            );
            
            // Добавляем обработчик клика
            placemark.events.add('click', function() {
                scrollToCard(point.id);
            });
            
            // Добавляем метку на карту
            yandexMapInstance.geoObjects.add(placemark);
            
        } catch(e) {
            console.error(`    ❌ Ошибка при создании метки ${index}:`, e);
        }
    });
    
    console.log(`✅ Добавлено меток: ${yandexMapInstance.geoObjects.getLength()}`);
    
    // Устанавливаем границы карты чтобы показать все метки
    if (uniquePoints.length > 0) {
        try {
            if (uniquePoints.length === 1) {
                yandexMapInstance.setCenter([uniquePoints[0].latitude, uniquePoints[0].longitude], 15);
            } else {
                const bounds = yandexMapInstance.geoObjects.getBounds();
                if (bounds && bounds[0] && bounds[1]) {
                    yandexMapInstance.setBounds(bounds, {
                        checkZoomRange: true,
                        zoomMargin: 50
                    });
                } else {
                    yandexMapInstance.setCenter([uniquePoints[0].latitude, uniquePoints[0].longitude], 12);
                }
            }
        } catch(e) {
            console.warn('⚠️ Ошибка установки границ:', e);
            yandexMapInstance.setCenter([uniquePoints[0].latitude, uniquePoints[0].longitude], 12);
        }
    }
}

// Замените функцию initMap в map.js на эту версию
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
    let linksData = getMapData();
    
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
    
    // 🔥 НОВЫЙ ПОДХОД: Собираем ВСЕ уникальные координаты напрямую из данных
    let allPoints = [];
    let seenCoordinates = new Map(); // Для отслеживания уникальных координат
    
    linksData.forEach((item, idx) => {
        console.log(`\n🔍 Обработка элемента ${idx}:`);
        console.log(`  Название: ${item.college_name || item.name || 'Без названия'}`);
        
        // 1. Проверяем прямые координаты в объекте
        if (item.latitude && item.longitude) {
            const lat = normalizeCoordinate(item.latitude);
            const lon = normalizeCoordinate(item.longitude);
            const address = item.program_address || item.map_address || item.address || 'Адрес не указан';
            
            console.log(`  📍 Прямые координаты: lat=${lat}, lon=${lon}, адрес: ${address}`);
            
            if (!isNaN(lat) && !isNaN(lon) && lat !== 0 && lon !== 0) {
                const coordKey = `${lat.toFixed(6)},${lon.toFixed(6)}`;
                
                if (!seenCoordinates.has(coordKey)) {
                    seenCoordinates.set(coordKey, {
                        id: item.id,
                        name: item.college_name,
                        program: item.program_name_in_bundle || item.program_name,
                        address: address,
                        latitude: lat,
                        longitude: lon,
                        type: 'program'
                    });
                    allPoints.push(seenCoordinates.get(coordKey));
                    console.log(`    ✅ Новая точка добавлена: ${address}`);
                } else {
                    console.log(`    ⏭️ Точка уже существует: ${address}`);
                }
            }
        }
        
        // 2. Проверяем map_points
        if (item.map_points && Array.isArray(item.map_points)) {
            console.log(`  📍 Найдено map_points: ${item.map_points.length}`);
            
            item.map_points.forEach((point, pointIdx) => {
                const lat = normalizeCoordinate(point.latitude);
                const lon = normalizeCoordinate(point.longitude);
                const address = point.address || 'Адрес не указан';
                
                console.log(`    Точка ${pointIdx}: lat=${lat}, lon=${lon}, адрес: ${address}`);
                
                if (!isNaN(lat) && !isNaN(lon) && lat !== 0 && lon !== 0) {
                    const coordKey = `${lat.toFixed(6)},${lon.toFixed(6)}`;
                    
                    if (!seenCoordinates.has(coordKey)) {
                        seenCoordinates.set(coordKey, {
                            id: item.id,
                            name: item.college_name,
                            program: item.program_name_in_bundle || item.program_name,
                            address: address,
                            latitude: lat,
                            longitude: lon,
                            type: point.type || 'regular'
                        });
                        allPoints.push(seenCoordinates.get(coordKey));
                        console.log(`      ✅ Новая точка добавлена: ${address}`);
                    } else {
                        console.log(`      ⏭️ Точка уже существует: ${address}`);
                    }
                } else {
                    console.warn(`      ❌ Некорректные координаты`);
                }
            });
        }
        
        // 3. 🔥 НОВОЕ: Проверяем admission_addresses и regular_addresses
        if (item.admission_addresses && Array.isArray(item.admission_addresses)) {
            item.admission_addresses.forEach((addr, addrIdx) => {
                const lat = normalizeCoordinate(addr.latitude);
                const lon = normalizeCoordinate(addr.longitude);
                const address = addr.address || 'Адрес приёмной комиссии';
                
                if (!isNaN(lat) && !isNaN(lon) && lat !== 0 && lon !== 0) {
                    const coordKey = `${lat.toFixed(6)},${lon.toFixed(6)}`;
                    
                    if (!seenCoordinates.has(coordKey)) {
                        seenCoordinates.set(coordKey, {
                            id: item.id,
                            name: item.college_name,
                            program: item.program_name_in_bundle || item.program_name,
                            address: address,
                            latitude: lat,
                            longitude: lon,
                            type: 'admission'
                        });
                        allPoints.push(seenCoordinates.get(coordKey));
                        console.log(`    ✅ Добавлен адрес приёмной: ${address}`);
                    }
                }
            });
        }
        
        if (item.regular_addresses && Array.isArray(item.regular_addresses)) {
            item.regular_addresses.forEach((addr, addrIdx) => {
                const lat = normalizeCoordinate(addr.latitude);
                const lon = normalizeCoordinate(addr.longitude);
                const address = addr.address || 'Адрес';
                
                if (!isNaN(lat) && !isNaN(lon) && lat !== 0 && lon !== 0) {
                    const coordKey = `${lat.toFixed(6)},${lon.toFixed(6)}`;
                    
                    if (!seenCoordinates.has(coordKey)) {
                        seenCoordinates.set(coordKey, {
                            id: item.id,
                            name: item.college_name,
                            program: item.program_name_in_bundle || item.program_name,
                            address: address,
                            latitude: lat,
                            longitude: lon,
                            type: 'regular'
                        });
                        allPoints.push(seenCoordinates.get(coordKey));
                        console.log(`    ✅ Добавлен обычный адрес: ${address}`);
                    }
                }
            });
        }
    });
    
    console.log('\n📈 ИТОГОВАЯ СТАТИСТИКА:');
    console.log(`  Всего уникальных точек: ${allPoints.length}`);
    
    if (allPoints.length === 0) {
        console.log('❌ Нет точек с валидными координатами');
        if (mapElement) {
            mapElement.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">📍 Нет адресов с корректными координатами для отображения</div>';
        }
        return;
    }
    
    // Выводим все адреса для проверки
    console.log('  📍 Адреса для отображения:');
    allPoints.forEach((point, i) => {
        console.log(`    ${i+1}. ${point.address} (${point.latitude}, ${point.longitude})`);
    });
    
    // Инициализируем карту
    mapInitialized = true;
    
    try {
        // Вычисляем центр карты
        let centerLat = 59.93428;
        let centerLon = 30.3351;
        
        if (allPoints.length > 0) {
            const sumLat = allPoints.reduce((sum, p) => sum + p.latitude, 0);
            const sumLon = allPoints.reduce((sum, p) => sum + p.longitude, 0);
            centerLat = sumLat / allPoints.length;
            centerLon = sumLon / allPoints.length;
        }
        
        // Создаем карту
        yandexMapInstance = new ymaps.Map('map', {
            center: [centerLat, centerLon],
            zoom: 1,
            controls: ['zoomControl', 'fullscreenControl']
        });
        
        console.log('✅ Карта создана');
        
        // Добавляем метки (передаем ВСЕ точки, без дополнительной группировки)
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