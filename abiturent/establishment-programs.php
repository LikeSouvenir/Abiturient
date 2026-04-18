<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/data_processing.php';

// Обработка GET-параметров
$establishment_id_filter = isset($_GET['establishment_id']) ? (int)trim($_GET['establishment_id']) : null;
$page_title = 'Программы учебного заведения';
$links_data = [];
$current_establishment_name = null;
$json_options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;

if ($establishment_id_filter) {
    $current_establishment_name = getEstablishmentName($conn, $establishment_id_filter);
    if ($current_establishment_name) {
        $page_title = "" . htmlspecialchars($current_establishment_name);
        $raw_bundles_data = getProgramsByEstablishment($conn, $establishment_id_filter);
        $links_data = processProgramData($raw_bundles_data);
    } else {
        $page_title = "Учебное заведение не найдено";
        $establishment_id_filter = null;
    }
} else {
    $page_title = "Учебное заведение не выбрано";
}

$additional_css = 'assets/css/style.css';
include __DIR__ . '/templates/header.php';

?>

<main class="container">
    <div class="left-column">
        <h2 class="main-title" id="pageMainTitle"><?= htmlspecialchars($page_title) ?></h2>
        <div class="link-list" id="linkList">
            <?php if (!$establishment_id_filter): ?>
                <p class="no-results php-message">Учебное заведение не выбрано. Пожалуйста, укажите ID учебного заведения.</p>
            <?php elseif (empty($links_data) && $current_establishment_name): ?>
                <p class="no-results php-message">В учебном заведении "<?= htmlspecialchars($current_establishment_name) ?>" пока нет доступных программ.</p>
            <?php elseif (empty($links_data) && !$current_establishment_name): ?>
                <p class="no-results php-message">Учебное заведение не найдено или не имеет программ.</p>
            <?php else: ?>
                <?php foreach ($links_data as $link): ?>
                    <?php include __DIR__ . '/templates/program_card.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/templates/map_template.php'; ?>
</main>

<!-- ПРЯМОЙ ЗАПРОС ДЛЯ КАРТЫ - используем $conn, который еще открыт -->
<script>
<?php
if ($establishment_id_filter && $current_establishment_name) {
    // Получаем все уникальные координаты из таблицы bundles
    $sql = "SELECT 
        b.id,
        b.program_address,
        b.program_latitude,
        b.program_longitude,
        e.name as college_name
    FROM bundles b
    JOIN establishments e ON b.establishment_id = e.id
    WHERE b.establishment_id = " . intval($establishment_id_filter) . "
    AND b.program_latitude IS NOT NULL 
    AND b.program_latitude != ''
    AND b.program_latitude != 0
    AND b.program_longitude IS NOT NULL 
    AND b.program_longitude != ''
    AND b.program_longitude != 0
    GROUP BY b.program_latitude, b.program_longitude, b.program_address";
    
    $result = $conn->query($sql);
    $points = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $points[] = [
                'id' => $row['id'],
                'name' => $row['college_name'],
                'address' => $row['program_address'],
                'latitude' => floatval($row['program_latitude']),
                'longitude' => floatval($row['program_longitude']),
                'type' => 'program'
            ];
        }
    }
    
    // Если нет координат в bundles, пробуем взять из таблицы addresses
    if (empty($points)) {
        $sql2 = "SELECT 
            a.id,
            a.address,
            a.latitude,
            a.longitude,
            a.admissions_committee,
            e.name as college_name
        FROM addresses a
        JOIN establishments e ON a.establishment_id = e.id
        WHERE a.establishment_id = " . intval($establishment_id_filter) . "
        AND a.latitude IS NOT NULL 
        AND a.latitude != ''
        AND a.latitude != 0
        AND a.longitude IS NOT NULL 
        AND a.longitude != ''
        AND a.longitude != 0
        GROUP BY a.latitude, a.longitude, a.address";
        
        $result2 = $conn->query($sql2);
        
        if ($result2 && $result2->num_rows > 0) {
            while ($row = $result2->fetch_assoc()) {
                $points[] = [
                    'id' => $row['id'],
                    'name' => $row['college_name'],
                    'address' => $row['address'],
                    'latitude' => floatval($row['latitude']),
                    'longitude' => floatval($row['longitude']),
                    'type' => $row['admissions_committee'] == 1 ? 'admission' : 'regular'
                ];
            }
        }
    }
    
    echo "window.mapData = " . json_encode($points, JSON_UNESCAPED_UNICODE) . ";\n";
    echo "window.linksDataForMap = window.mapData;\n";
    echo "console.log('✅ Найдено точек для карты:', " . count($points) . ");\n";
    
    if (count($points) > 0) {
        $addresses = array_column($points, 'address');
        echo "console.log('📍 Адреса:', " . json_encode($addresses, JSON_UNESCAPED_UNICODE) . ");\n";
    }
}
?>
</script>

<script src="assets/js/map.js"></script>
</body>
</html>

<?php
// Закрываем соединение САМЫМ ПОСЛЕДНИМ
$conn->close();
?>