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
        $page_title = "Колледжи по программе: " . htmlspecialchars($display_program_name);
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
?>

<?php include __DIR__ . '/templates/header.php'; ?>

<main class="container">
    <div class="left-column">
        <h2 class="main-title" id="pageMainTitle"><?= htmlspecialchars($page_title) ?></h2>
        <div class="link-list" id="linkList">
            <?php if (empty($links_data)): ?>
                <p class="no-results">Подходящие варианты не найдены.</p>
            <?php else: ?>
                <?php foreach ($links_data as $link): ?>
                    <?php include __DIR__ . '/templates/establishment_card.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/templates/map_template.php'; ?>
</main>

<script>
    document.getElementById('map').dataset.links = '<?= addslashes(json_encode($links_data, $json_options)) ?>';
</script>
<script src="assets/js/map.js" defer></script>

</body>
</html>