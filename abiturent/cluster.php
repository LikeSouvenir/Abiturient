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
        $page_title = "Программы: " . htmlspecialchars($current_establishment_name);
        $raw_bundles_data = getProgramsByEstablishment($conn, $establishment_id_filter);
        $links_data = processProgramData($raw_bundles_data);
    } else {
        $page_title = "Учебное заведение не найдено";
        $establishment_id_filter = null;
    }
} else {
    $page_title = "Учебное заведение не выбрано";
}

$conn->close();
?>

<?php include __DIR__ . '/templates/header.php'; ?>

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

<script>
    document.getElementById('map').dataset.links = '<?= addslashes(json_encode($links_data, $json_options)) ?>';
</script>
<script src="assets/js/map.js" defer></script>

</body>
</html>