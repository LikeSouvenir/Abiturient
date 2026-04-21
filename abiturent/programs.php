<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/includes/functions.php';

$direction_code_identifier = isset($_GET['direction_code']) ? trim($_GET['direction_code']) : null;
$direction_name_display = 'Направление не указано';
$programs_list = [];
$direction_found = false;
$json_options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;

if (!empty($direction_code_identifier)) {
    $direction = getDirectionByCode($conn, $direction_code_identifier);

    if ($direction) {
        $direction_found = true;
        $direction_name_display = $direction['name'];
        $direction_id = $direction['id'];
        $programs_list = getProgramsByDirection($conn, $direction_id);
    } else {
        $direction_name_display = 'Направление с кодом "' . htmlspecialchars($direction_code_identifier) . '" не найдено';
    }
}

$conn->close();

$page_title = 'Программы: ' . $direction_name_display;
$additional_css = 'assets/css/direction-programs.css';
include __DIR__ . '/templates/header.php';
?>

<main class="container">
    <div class="content-wrapper">
        <h1 class="title" id="directionTitle">
            <?php echo $direction_code_identifier . ": " . htmlspecialchars(strtoupper($direction_name_display)); ?>
        </h1>
        
        <div class="program-grid" id="programGridContainer">
            <?php if (empty($programs_list)): ?>
                <?php if ($direction_found): ?>
                    <p class="no-results">Программы по этому направлению не найдены или не имеют учебных заведений.</p>
                <?php elseif (!empty($direction_code_identifier)): ?>
                    <p class="no-results">Направление с кодом "<?php echo htmlspecialchars($direction_code_identifier); ?>" не найдено.</p>
                <?php else: ?>
                    <p class="no-results">Направление не указано.</p>
                <?php endif; ?>
            <?php else: ?>
                <?php foreach ($programs_list as $program): 
                    $search_text = strtolower(htmlspecialchars(
                        "{$program['name']} {$program['program_code']} {$program['keywords']} " . implode(' ', $program['attributes_array'])
                    ));
                ?>
                    <a href="establishments.php?program_code=<?php echo urlencode($program['program_code']); ?>" 
                       class="program-card" 
                       data-search-text="<?php echo $search_text; ?>">
                        
                        <img src="<?php echo htmlspecialchars($program['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($program['name']); ?>">
                        
                        <div class="program-details">
                            <h3><?php echo htmlspecialchars($program['name']); ?></h3>
                            <p>Код программы: <?php echo htmlspecialchars($program['program_code']); ?></p>
                            <p>Учебных заведений: <?php echo htmlspecialchars($program['num_establishments']); ?></p>
                            <div class="tags">
                                <?php echo getProgramTags($program); ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script src="assets/js/direction-programs.js"></script>

</body>
</html>