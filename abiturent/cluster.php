<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/data_processing.php';

// Обработка GET-параметров - теперь работаем с cluster_id
$cluster_id = isset($_GET['cluster_id']) ? (int)trim($_GET['cluster_id']) : null;
$page_title = 'Программы кластера';
$links_data = [];
$current_cluster_name = null;
$json_options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;

if ($cluster_id) {
    // Получаем название кластера (нужно создать эту функцию или получить по-другому)
    $current_cluster_name = getClusterName($conn, $cluster_id);
    
    if ($current_cluster_name) {
        $page_title = "Программы кластера: " . htmlspecialchars($current_cluster_name);
        // Получаем программы по cluster_id (нужно создать эту функцию)
        $raw_bundles_data = getProgramsByCluster($conn, $cluster_id);
        $links_data = processProgramData($raw_bundles_data);
    } else {
        $page_title = "Кластер не найден";
        $cluster_id = null;
    }
} else {
    $page_title = "Кластер не выбран";
}

$conn->close();
?>

<?php include __DIR__ . '/templates/header.php'; ?>

<main class="container">
    <div class="left-column">
        <h2 class="main-title" id="pageMainTitle"><?= htmlspecialchars($page_title) ?></h2>
        <div class="link-list" id="linkList">
            <?php if (!$cluster_id): ?>
                <p class="no-results php-message">Кластер не выбран. Пожалуйста, укажите ID кластера.</p>
            <?php elseif (empty($links_data) && $current_cluster_name): ?>
                <p class="no-results php-message">В кластере "<?= htmlspecialchars($current_cluster_name) ?>" пока нет доступных программ.</p>
            <?php elseif (empty($links_data) && !$current_cluster_name): ?>
                <p class="no-results php-message">Кластер не найден или не имеет программ.</p>
            <?php else: ?>
                <?php foreach ($links_data as $link): ?>
                    <?php include __DIR__ . '/templates/program_card.php'; ?>
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