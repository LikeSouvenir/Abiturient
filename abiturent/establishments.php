<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/data_processing.php';

// Обработка GET-параметров
$program_code_filter = isset($_GET['program_code']) ? trim($_GET['program_code']) : null;
$cluster_id_filter = isset($_GET['cluster_id']) ? (int)trim($_GET['cluster_id']) : null;

$page_title = 'Учебные заведения';
$links_data = [];
$display_program_name = null;
$json_options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;

// Получение данных
if ($program_code_filter) {
    $display_program_name = getProgramNameByCode($conn, $program_code_filter);
    if ($display_program_name) {
        $page_title = "Программа «" . htmlspecialchars($display_program_name) . "»";
    }
} elseif ($cluster_id_filter) {
    $cluster_name = getClusterNameById($conn, $cluster_id_filter);
    if ($cluster_name) {
        $page_title = "Профессионалитет: " . htmlspecialchars($cluster_name);
    } else {
        $page_title = "Профессионалитет: Кластер не найден";
    }
}

$raw_bundles_data = getEstablishmentsByFilters($conn, $program_code_filter, $cluster_id_filter);
$links_data = processEstablishmentData($raw_bundles_data, $cluster_id_filter, $program_code_filter);

$conn->close();

$additional_css = 'assets/css/style.css';
include __DIR__ . '/templates/header.php';
?>

<main class="container">
    <div class="left-column">
        <h2 class="main-title" id="pageMainTitle"><?= htmlspecialchars($page_title) ?></h2>
        <div class="link-list" id="linkList">
            <?php if (empty($links_data)): ?>
                <p class="no-results">Подходящие варианты не найдены.</p>
            <?php else: ?>
                <?php foreach ($links_data as $link): ?>
                    <?php include __DIR__ . '/templates/program_card.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/templates/map_template.php'; ?>
</main>

<!-- 🔧 СНАЧАЛА ОПРЕДЕЛЯЕМ ДАННЫЕ ДЛЯ КАРТЫ -->
<script>
// Определяем window.mapData ДО загрузки map.js
window.mapData = <?= json_encode($links_data, $json_options) ?>;

console.log('📊 window.mapData установлен, количество записей:', window.mapData.length);
console.log('📋 Пример первой записи:', window.mapData[0]);

// Проверяем координаты
let hasValidCoordinates = false;
if (window.mapData && window.mapData.length > 0) {
    window.mapData.forEach((item, index) => {
        if (index === 0) {
            console.log(`\n📍 Пример элемента 0:`, item);
        }
        
        // Проверяем map_points
        if (item.map_points && Array.isArray(item.map_points)) {
            item.map_points.forEach(point => {
                if (point.latitude && point.longitude && 
                    point.latitude !== null && point.longitude !== null) {
                    hasValidCoordinates = true;
                }
            });
        }
    });
}

console.log(`📈 Есть валидные координаты: ${hasValidCoordinates}`);

// Устанавливаем dataset для обратной совместимости
document.addEventListener('DOMContentLoaded', function() {
    const mapElement = document.getElementById('map');
    if (mapElement) {
        mapElement.dataset.links = JSON.stringify(window.mapData);
        console.log('✅ dataset.links установлен');
    }
});
</script>

<!-- 🔧 ЗАГРУЖАЕМ map.js ПОСЛЕ определения данных -->
<script src="assets/js/map.js"></script>

</body>
</html>