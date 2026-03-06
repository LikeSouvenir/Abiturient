<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/admin/config.php';

$establishment_id_filter = isset($_GET['establishment_id']) ? (int)trim($_GET['establishment_id']) : null;

$page_title = 'Программы учебного заведения';
$links_data = [];
$current_establishment_name = null;
$json_options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;

if ($establishment_id_filter) {
    $stmt_est_name = $conn->prepare("SELECT name FROM establishments WHERE id = ?");
    $stmt_est_name->bind_param("i", $establishment_id_filter);
    $stmt_est_name->execute();
    $est_name_res = $stmt_est_name->get_result();
    if ($est_data = $est_name_res->fetch_assoc()) {
        $current_establishment_name = $est_data['name'];
        $page_title = "Программы: " . htmlspecialchars($current_establishment_name);
    } else {
        $page_title = "Учебное заведение не найдено";
        $establishment_id_filter = null; 
    }
    if ($est_name_res) $est_name_res->close();
    $stmt_est_name->close();

    if ($establishment_id_filter && $current_establishment_name) {
        $sql_select_bundles = "
            SELECT
                b.id as bundle_id,
                b.education_type,
                b.education_base,
                b.duration,
                b.program_address,
                b.program_latitude,
                b.program_longitude,
                b.cluster_id,
                e.id as establishment_id,
                e.name as establishment_name,
                e.logo_path as establishment_logo_path,
                e.address as establishment_main_address,
                e.latitude as establishment_main_latitude,
                e.longitude as establishment_main_longitude,
                e.phone as establishment_phone,
                e.website as establishment_website,
                p.id as program_id,
                p.name as program_name,
                p.program_code as program_code_val,
                p.attributes as program_attributes,
                cls.name as actual_cluster_name
            FROM bundles b
            JOIN establishments e ON b.establishment_id = e.id
            JOIN programs p ON b.program_id = p.id
            LEFT JOIN clusters cls ON b.cluster_id = cls.id
            WHERE b.establishment_id = ?
            ORDER BY p.name
        ";

        $stmt_links = $conn->prepare($sql_select_bundles);
        $stmt_links->bind_param("i", $establishment_id_filter);
        $stmt_links->execute();
        $result_links = $stmt_links->get_result();
        $raw_bundles_data = $result_links->fetch_all(MYSQLI_ASSOC);
        if ($result_links) $result_links->close();
        $stmt_links->close();

        foreach($raw_bundles_data as $bundle) {
            $link_item = [];
            $link_item['id'] = $bundle['bundle_id'];
            $link_item['college_name'] = $bundle['establishment_name'];
            $link_item['college_logo_path'] = $bundle['establishment_logo_path'] ?  "./uploads/establishments/".$bundle['establishment_logo_path'] : "uploads/establishments/placeholder.svg";
            $link_item['program_name_in_bundle'] = $bundle['program_name'];
            $link_item['show_program_name_in_card'] = false; 
            $link_item['education_type'] = $bundle['education_type'];
            $link_item['base_level'] = $bundle['education_base'];
            $link_item['duration'] = $bundle['duration'];
            $link_item['program_address'] = $bundle['program_address'];
            $link_item['commission_address'] = $bundle['establishment_main_address'];
            $link_item['phone'] = $bundle['establishment_phone'];
            $link_item['website'] = $bundle['establishment_website'];
            $link_item['program_attributes_array'] = !empty($bundle['program_attributes']) ? array_map('trim', explode(',', $bundle['program_attributes'])) : [];
            $link_item['is_professionalitet'] = !empty($bundle['cluster_id']);
            $link_item['cluster_name_on_card'] = $bundle['actual_cluster_name'];
            $link_item['latitude'] = $bundle['program_latitude'] ? $bundle['program_latitude'] : $bundle['establishment_main_latitude'];
            $link_item['longitude'] = $bundle['program_longitude'] ? $bundle['program_longitude'] : $bundle['establishment_main_longitude'];
            $link_item['map_address'] = $bundle['program_address'] ? $bundle['program_address'] : $bundle['establishment_main_address'];
            $link_item['search_text'] = strtolower(
                ($bundle['establishment_name'] ?? '') . " " .
                ($bundle['program_name'] ?? '') . " " .
                ($bundle['education_type'] ?? '') . " " .
                ($bundle['education_base'] ?? '') . " " .
                ($bundle['program_address'] ?? '') . " " .
                ($bundle['establishment_main_address'] ?? '') . " " .
                implode(' ', $link_item['program_attributes_array']) .
                ($link_item['is_professionalitet'] ? " профессионалитет " . ($bundle['actual_cluster_name'] ?? '') : "")
            );
            $links_data[] = $link_item;
        }
    }
} else {
    $page_title = "Учебное заведение не выбрано";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ИС "Абитуриент" - <?php echo htmlspecialchars($page_title); ?></title>
    <script src="https://api-maps.yandex.ru/2.1/?apikey=YOUR_YANDEX_MAPS_API_KEY&lang=ru_RU" type="text/javascript"></script>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f7f6; display: flex; flex-direction: column; align-items: center; padding: 20px; }
        .header { width: 100%; max-width: 1200px; display: flex; align-items: center; background-color: #fff; padding: 10px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); margin-bottom: 20px; box-sizing: border-box; }
        .search-area { display: flex; align-items: center; flex-grow: 1; }
        .search-container { display: flex; align-items: center; flex-grow: 1; border: 1px solid #ccc; border-radius: 4px; overflow: hidden; margin-right: 10px; }
        .search-input { border: none; padding: 8px 10px; flex-grow: 1; outline: none; font-size: 1rem; }
        .search-container svg { margin: 0 8px; color: #555; }
        .header-button { background-color: transparent; border: none; padding: 8px 10px; cursor: pointer; display: flex; align-items: center; }
        .back-button { margin-right: 10px; }
        .container { width: 100%; max-width: 1200px; display: flex; gap: 20px; flex: 1; }
        .left-column { flex: 1; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); overflow-y: auto; max-height: calc(100vh - 160px); display: flex; flex-direction: column; }
        .right-column { flex: 1; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); display: flex; flex-direction: column; }
        .main-title { margin-top: 0; margin-bottom: 15px; font-size: 1.4em; color: #2c3e50; text-align: center; }
        .link-list { margin-top: 0; flex-grow: 1; }
        .link-item { display: flex; align-items: flex-start; border: 1px solid #eee; border-radius: 6px; padding: 15px; margin-bottom: 15px; background-color: #fff; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); transition: background-color 0.2s ease, box-shadow 0.2s; cursor: pointer; }
        .link-item:hover { background-color: #f9f9f9; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
        .college-logo { width: 60px; height: 60px; margin-right: 15px; border-radius: 4px; display: flex; justify-content: center; align-items: center; overflow: hidden; flex-shrink: 0; background-color: #e9ecef; }
        .college-logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .link-details { flex-grow: 1; }
        .link-details h3 { margin-top: 0; margin-bottom: 8px; font-size: 1.15rem; color: #333; }
        .link-details .program-name-in-card { font-size: 1rem; color: #555; margin-bottom: 6px; display:block; font-weight: bold; }
        .link-details p { margin: 4px 0; font-size: 0.9rem; color: #555; line-height: 1.5; }
        .link-details p strong { color: #333; }
        .college-website a { color: #007bff; text-decoration: none; }
        .college-website a:hover { text-decoration: underline; }
        .tags-container { margin-top: 8px; }
        .attribute-tag { display: inline-block; background-color: #e7f3ff; color: #0056b3; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem; margin-right: 5px; margin-bottom: 5px; }
        .oge-attribute { background-color: #d4edda; color: #155724; }
        .professionalitet-tag { background-color: #fff3cd; color: #856404; }
        .map-title { margin-top: 0; margin-bottom: 15px; font-size: 1.3em; color: #333; }
        .map-container { flex-grow: 1; min-height: 400px; border-radius: 4px; overflow: hidden; border: 1px solid #ddd; }
        .loading, .no-results, .js-search-no-results { text-align: center; padding: 20px; font-size: 1.2em; color: #555; width: 100%; }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { padding: 10px; margin-bottom: 10px; flex-wrap: wrap; }
            .search-area { width: 100%; margin-right: 0; margin-bottom: 10px; order: 2; }
            .header-button.back-button { margin-right: 10px; order: 1; }
            .header-button.filter-button { order: 3; }
            .container { flex-direction: column; }
            .left-column, .right-column { flex: none; width: 100%; max-height: none; box-sizing: border-box; }
            .left-column { max-height: 55vh; margin-bottom: 10px; }
            .map-container { min-height: 300px; }
        }
    </style>
</head>
<body>
    <header class="header">
        <button class="header-button back-button" id="backButton" title="Назад">
             <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20px" height="20px">
                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
            </svg>
        </button>
        <div class="search-area">
            <div class="search-container">
                 <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20px" height="20px">
                    <path d="M10 2a8 8 0 0 1 6.32 12.9L20.69 18.3a1 1 0 0 1-1.41 1.41l-4.39-4.38A8 8 0 1 1 10 2zm0 2a6 6 0 1 0 0 12A6 6 0 0 0 10 4z"/>
                </svg>
                <input type="text" class="search-input" id="linkSearchInput" placeholder="Поиск по программам...">
            </div>
             <button class="header-button filter-button" id="goHomeButtonEst" title="На главную"> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20px" height="20px">
                   <path d="M12 3.198l-10 9.143V22h5V14h6v8h5V12.34L12 3.198z"/>
                </svg>
            </button>
        </div>
    </header>
    <main class="container">
        <div class="left-column">
            <h2 class="main-title" id="pageMainTitle"><?php echo htmlspecialchars($page_title); ?></h2>
            <div class="link-list" id="linkList">
                <?php if (!$establishment_id_filter): ?>
                    <p class="no-results php-message">Учебное заведение не выбрано. Пожалуйста, укажите ID учебного заведения.</p>
                <?php elseif (empty($links_data) && $current_establishment_name): ?>
                    <p class="no-results php-message">В учебном заведении "<?php echo htmlspecialchars($current_establishment_name); ?>" пока нет доступных программ.</p>
                <?php elseif (empty($links_data) && !$current_establishment_name): ?>
                     <p class="no-results php-message">Учебное заведение не найдено или не имеет программ.</p>
                <?php else: ?>
                    <?php foreach ($links_data as $link): ?>
                        <div class="link-item" data-id="<?php echo $link['id']; ?>" data-search-text="<?php echo htmlspecialchars($link['search_text']); ?>" data-lat="<?php echo htmlspecialchars($link['latitude']); ?>" data-lon="<?php echo htmlspecialchars($link['longitude']); ?>">
                            <div class="college-logo">
                                <img src="<?php echo htmlspecialchars($link['college_logo_path']); ?>" alt="Логотип <?php echo htmlspecialchars($link['college_name']); ?>">
                            </div>
                            <div class="link-details">
                                <span class="program-name-in-card"><?php echo htmlspecialchars($link['program_name_in_bundle']); ?></span>
                                <?php if (!empty($link['education_type'])): ?><p><strong>Образование:</strong> <?php echo htmlspecialchars($link['education_type']); ?></p><?php endif; ?>
                                <?php if (!empty($link['base_level'])): ?><p><strong>На базе:</strong> <?php echo htmlspecialchars($link['base_level']); ?></p><?php endif; ?>
                                <?php if (!empty($link['duration'])): ?><p><strong>Срок обучения:</strong> <?php echo htmlspecialchars($link['duration']); ?></p><?php endif; ?>
                                <?php if (!empty($link['program_address'])): ?><p><strong>Адрес проведения программы:</strong> <?php echo htmlspecialchars($link['program_address']); ?></p><?php endif; ?>
                                <?php if (!empty($link['commission_address']) && $link['commission_address'] !== $link['program_address']): ?>
                                    <p><strong>Адрес приемной комиссии:</strong> <?php echo htmlspecialchars($link['commission_address']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($link['phone'])): ?><p><strong>Телефон:</strong> <?php echo htmlspecialchars($link['phone']); ?></p><?php endif; ?>
                                <?php if (!empty($link['website'])): ?><p class="college-website"><strong>Сайт:</strong> <a href="<?php echo htmlspecialchars($link['website']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($link['website']); ?></a></p><?php endif; ?>
                                <div class="tags-container">
                                    <?php if (!empty($link['program_attributes_array'])): ?>
                                        <?php foreach ($link['program_attributes_array'] as $attr):
                                              $attr_lower = mb_strtolower(trim($attr));
                                              $tag_class = 'attribute-tag';
                                              if (strpos($attr_lower, '2 огэ') !== false) { $tag_class .= ' oge-attribute'; continue; }
                                        ?>
                                            <span class="<?php echo $tag_class; ?>"><?php echo htmlspecialchars($attr); ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if ($link['is_professionalitet']): ?>
                                        <span class="attribute-tag professionalitet-tag">
                                            Профессионалитет<?php echo !empty($link['cluster_name_on_card']) ? ': ' . htmlspecialchars($link['cluster_name_on_card']) : ''; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="right-column">
            <h3 class="map-title">Карта программ учебного заведения</h3>
            <div id="map" class="map-container">
                <?php if (!$establishment_id_filter || empty($links_data) || !array_filter($links_data, function($link_item) { return !empty($link_item['latitude']) && !empty($link_item['longitude']) || !empty($link_item['map_address']); })): ?>
                     <p class="no-results php-map-message" style="padding-top: 40px;">Нет данных для отображения на карте.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script>
    let yandexMapInstance;
    let placemarksCollection;
    const linksDataForMap = <?php echo ($establishment_id_filter && !empty($links_data)) ? json_encode(array_values($links_data), $json_options) : '[]'; ?>;

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
        if(initialNoDataMsgMap) initialNoDataMsgMap.style.display = 'none';

        try {
            yandexMapInstance = new ymaps.Map('map', {
                center: [59.9343, 30.3351], zoom: 10,
                controls: ['zoomControl', 'typeSelector', 'fullscreenControl', 'geolocationControl']
            });
            placemarksCollection = new ymaps.GeoObjectCollection({}, { preset: 'islands#blueDotIcon' });
            yandexMapInstance.geoObjects.add(placemarksCollection);

            const geocodingPromises = [];
            const allPlacemarksData = [];

            linksDataForMap.forEach((link) => {
                const collegeName = link.college_name || '<?php echo htmlspecialchars($current_establishment_name ?? "Учебное заведение"); ?>';
                const programNameForBalloon = link.program_name_in_bundle || 'Программа';
                let balloonContent = `<h3>${programNameForBalloon}</h3>`; // Program name as main header
                balloonContent += `<p><strong>Учебное заведение:</strong> ${collegeName}</p>`;
                if(link.education_type) balloonContent += `<p><strong>Образование:</strong> ${link.education_type}</p>`;
                if(link.base_level) balloonContent += `<p><strong>На базе:</strong> ${link.base_level}</p>`;
                if(link.duration) balloonContent += `<p><strong>Срок обучения:</strong> ${link.duration}</p>`;
                if(link.program_address) balloonContent += `<p><strong>Адрес проведения:</strong> ${link.program_address}</p>`;
                if(link.commission_address && link.commission_address !== link.program_address) balloonContent += `<p><strong>Адрес приемной комиссии:</strong> ${link.commission_address}</p>`;
                if(link.phone) balloonContent += `<p><strong>Телефон:</strong> ${link.phone}</p>`;
                if(link.website) balloonContent += `<p class="college-website"><strong>Сайт:</strong> <a href="${link.website}" target="_blank" rel="noopener noreferrer">${link.website}</a></p>`;
                let tagsHtml = "";
                if (link.program_attributes_array && link.program_attributes_array.length > 0) {
                    link.program_attributes_array.forEach(attr => { tagsHtml += `<span class="attribute-tag" style="background-color:#e7f3ff; color:#0056b3; padding:2px 5px; border-radius:3px; font-size:0.8em; margin-right:3px;">${attr}</span> `});
                }
                if (link.is_professionalitet) {
                    tagsHtml += `<span class="attribute-tag professionalitet-tag" style="background-color:#fff3cd; color:#856404; padding:2px 5px; border-radius:3px; font-size:0.8em; margin-right:3px;">Профессионалитет${link.cluster_name_on_card ? ': ' + link.cluster_name_on_card : ''}</span>`;
                }
                if(tagsHtml) balloonContent += `<div style="margin-top:5px;">${tagsHtml}</div>`;

                function addPlacemarkDataToList(coords, currentLinkData) {
                     allPlacemarksData.push({
                        coords: coords,
                        id: currentLinkData.id,
                        properties: { balloonContentHeader: programNameForBalloon, balloonContentBody: balloonContent, hintContent: programNameForBalloon + ' - ' + collegeName },
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
                        }, function(err){
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
                                if (placemark.balloon.isOpen()) { placemark.balloon.close(); } else { placemark.balloon.open(); }
                            });
                        }
                    });
                    if (allPlacemarksData.length > 0) {
                        if(initialNoDataMsgMap) initialNoDataMsgMap.style.display = 'none';
                        yandexMapInstance.setBounds(placemarksCollection.getBounds(), { checkZoomRange: true, zoomMargin: 35 });
                    } else {
                        const hasPotentiallyMappableData = linksDataForMap.some(link => (link.latitude && link.longitude) || (link.map_address && link.map_address !== 'Адрес не указан'));
                        if (initialNoDataMsgMap) { initialNoDataMsgMap.style.display = 'block'; }
                        else if (hasPotentiallyMappableData) { mapElement.innerHTML = '<p class="no-results" style="padding-top: 40px;">Не удалось определить координаты для отображения на карте.</p>'; }
                        else if (linksDataForMap.length > 0 && !initialNoDataMsgMap) { mapElement.innerHTML = '<p class="no-results" style="padding-top: 40px;">Данные есть, но нет информации для отображения на карте.</p>';}
                        else if (!initialNoDataMsgMap) { mapElement.innerHTML = '<p class="no-results" style="padding-top: 40px;">Нет данных для отображения на карте.</p>'; }
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
        if (typeof ymaps !== 'undefined') { ymaps.ready(initMap); }
        else {
            const mapElement = document.getElementById('map');
            const initialNoDataMsgMap = mapElement.querySelector('.php-map-message');
            if(initialNoDataMsgMap) initialNoDataMsgMap.style.display = 'none';
            if(mapElement) mapElement.innerHTML = '<p class="no-results" style="padding-top: 40px;">API Яндекс.Карт не загружено.</p>';
        }
        const searchInput = document.getElementById('linkSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => { filterLinks(e.target.value); });
            if (searchInput.value) { filterLinks(searchInput.value); }
        }
        const backButton = document.getElementById('backButton');
        if (backButton) { backButton.addEventListener('click', () => { history.back(); }); }
        const goHomeButtonEst = document.getElementById('goHomeButtonEst');
        if (goHomeButtonEst) { goHomeButtonEst.addEventListener('click', () => { window.location.href = 'index.php'; }); }

        const phpMessageInList = document.querySelector('#linkList > .php-message');
        if (linksDataForMap.length > 0 && phpMessageInList) {
            phpMessageInList.style.display = 'none';
        }
    });
    </script>
</body>
</html>