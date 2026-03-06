<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/admin/config.php';

$direction_code_identifier = isset($_GET['direction_code']) ? trim($_GET['direction_code']) : null;
$direction_name_display = 'Направление не указано';
$programs_list = [];
$direction_found = false;
$json_options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;

if (!empty($direction_code_identifier)) {
    $stmt_dir = $conn->prepare("SELECT id, name FROM directions WHERE program_code_identifier = ?");
    $stmt_dir->bind_param("s", $direction_code_identifier);
    $stmt_dir->execute();
    $direction_result = $stmt_dir->get_result();
    $direction = $direction_result->fetch_assoc();

    if ($direction) {
        $direction_found = true;
        $direction_name_display = $direction['name'];
        $direction_id = $direction['id'];

        $stmt_prog = $conn->prepare("
            SELECT
                p.id, p.name, p.program_code, p.keywords, p.attributes, p.image_path,
                COUNT(DISTINCT b.establishment_id) as num_establishments,
                SUM(CASE WHEN b.cluster_id IS NOT NULL THEN 1 ELSE 0 END) > 0 as is_professionalitet_related
            FROM
                programs p
            LEFT JOIN
                bundles b ON p.id = b.program_id
            WHERE
                p.direction_id = ?
            GROUP BY
                p.id, p.name, p.program_code, p.keywords, p.attributes, p.image_path
            HAVING
                COUNT(DISTINCT b.establishment_id) > 0
            ORDER BY
                p.name
        ");
        $stmt_prog->bind_param("i", $direction_id);
        $stmt_prog->execute();
        $programs_result = $stmt_prog->get_result();
        
        while ($program_row = $programs_result->fetch_assoc()) {
            $program_row['image_path'] = $program_row['image_path'] ? "uploads/programs/" . $program_row['image_path'] : "uploads/programs/placeholder.svg";
            $program_row['attributes_array'] = !empty($program_row['attributes']) ? array_map('trim', explode(',', $program_row['attributes'])) : [];
            $programs_list[] = $program_row;
        }
        if ($programs_result) $programs_result->close();
        $stmt_prog->close();
    } else {
        $direction_name_display = 'Направление с кодом "' . htmlspecialchars($direction_code_identifier) . '" не найдено';
    }
    if ($direction_result) $direction_result->close();
    $stmt_dir->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ИС "Абитуриент" - Программы: <?php echo htmlspecialchars($direction_name_display); ?></title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f5f7;
      margin: 0;
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .header {
        width: 100%;
        max-width: 1200px;
        display: flex;
        align-items: center;
        background-color: #fff;
        padding: 10px 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        box-sizing: border-box;
    }
    .search-area {
         display: flex;
         align-items: center;
         flex-grow: 1;
    }
    .search-container {
        display: flex;
        align-items: center;
        flex-grow: 1;
        border: 1px solid #ccc;
        border-radius: 4px;
        overflow: hidden;
        margin-right: 10px;
    }
    .search-input {
        border: none;
        padding: 8px 10px;
        flex-grow: 1;
        outline: none;
        font-size: 1rem;
    }
    .search-container svg {
        margin: 0 8px;
        color: #555;
    }
    .header-button {
        background-color: transparent;
        border: none;
        padding: 8px 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
    }
    .back-button {
         margin-right: 10px;
    }
    .container {
      max-width: 1200px;
      margin: auto;
      padding: 0;
      width: 100%;
    }
    .content-wrapper {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        width: 100%;
        box-sizing: border-box;
    }
    .title {
      margin: 0 0 20px 0;
      font-weight: bold;
      font-size: 1.5em;
      color: #2c3e50;
      text-align: center;
    }
    .program-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: left;
    }
    .program-card {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      padding: 15px;
      display: flex;
      align-items: center;
      gap: 15px;
      text-decoration: none;
      color: inherit;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      width: calc(33.333% - 14px);
      min-width: 280px;
      box-sizing: border-box;
      border: 1px solid #eee;
    }
    .program-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .program-card img {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 4px;
      flex-shrink: 0;
    }
    .program-details {
         flex-grow: 1;
    }
    .program-card h3 {
      font-size: 1.05rem;
      margin: 0 0 8px;
      font-weight: bold;
      color: #333;
    }
    .program-card p {
      margin: 5px 0;
      font-size: 0.85rem;
      color: #555;
    }
    .program-card .tags {
        margin-top: 8px;
        line-height: 1.6;
    }
    .program-card .attribute-tag {
        display: inline-block;
        background-color: #e7f3ff;
        color: #0056b3;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        margin-right: 5px;
        margin-bottom: 5px;
        font-weight: 500;
    }
    .program-card .oge-attribute {
        background-color: #fc8372;
        color: white;
    }
    .program-card .professionalitet-tag {
        background-color: #fff3cd;
        color: #856404;
    }
    .program-card .profession {
        background-color: #5ef2a0;
        color: #0056b3;
    }
    .no-results, .js-no-results-message {
        text-align: center;
        padding: 20px;
        font-size: 1.2em;
        color: #555;
        width: 100%;
    }
    @media (max-width: 992px) {
        .program-card {
            width: calc(50% - 10px);
        }
    }
     @media (max-width: 768px) {
         body { padding: 10px; }
         .content-wrapper { padding: 15px; }
         .program-card { width: 100%; min-width: auto; }
         .header { padding-left: 10px; padding-right: 10px; flex-wrap: wrap; }
         .search-area { width: 100%; margin-right: 0; margin-bottom: 10px; order: 2; }
         .header-button.back-button { order: 1; }
         .header-button.filter-button { order: 3; }
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
                <input type="text" placeholder="Поиск по программам..." class="search-input" id="programSearchInput">
            </div>
             <button class="header-button filter-button" id="goHomeButton" title="На главную"> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20px" height="20px">
                   <path d="M12 3.198l-10 9.143V22h5V14h6v8h5V12.34L12 3.198z"/>
                </svg>
            </button>
        </div>
  </header>
  <div class="container">
    <div class="content-wrapper">
        <h1 class="title" id="directionTitle"> <?php echo $direction_code_identifier.": ".htmlspecialchars(strtoupper($direction_name_display)); ?></h1>
        <div class="program-grid" id="programGridContainer">
          <?php if (empty($programs_list)): ?>
              <?php if ($direction_found): ?>
                <p class="no-results php-no-results-message">Программы по этому направлению не найдены или не имеют учебных заведений.</p>
              <?php elseif (!empty($direction_code_identifier)): ?>
                <p class="no-results php-no-results-message">Направление с кодом "<?php echo htmlspecialchars($direction_code_identifier); ?>" не найдено.</p>
              <?php else: ?>
                <p class="no-results php-no-results-message">Направление не указано.</p>
              <?php endif; ?>
          <?php else: ?>
              <?php foreach ($programs_list as $program): ?>
                  <a href="establishments.php?program_code=<?php echo urlencode($program['program_code']); ?>" class="program-card" data-search-text="<?php echo strtolower(htmlspecialchars("{$program['name']} {$program['program_code']} {$program['keywords']} " . implode(' ', $program['attributes_array']))); ?>">
                      <img src="<?php echo htmlspecialchars($program['image_path']); ?>" alt="<?php echo htmlspecialchars($program['name']); ?>">
                      <div class="program-details">
                          <h3><?php echo htmlspecialchars($program['name']); ?></h3>
                          <p>Код программы: <?php echo htmlspecialchars($program['program_code']); ?></p>
                          <p>Учебных заведений: <?php echo htmlspecialchars($program['num_establishments']); ?></p>
                          <div class="tags">
                              <?php if ($program['is_professionalitet_related']): ?>
                                  <span class="attribute-tag professionalitet-tag">Профессионалитет</span>
                              <?php endif; ?>
                              <?php if (!empty($program['attributes_array'])): ?>
                                  <?php foreach ($program['attributes_array'] as $attr):
                                        $attr_lower = mb_strtolower(trim($attr));
                                        $tag_class = 'attribute-tag';
                                        if (strpos($attr_lower, '2 огэ') !== false) {
                                            continue;
                                            // $tag_class .= ' oge-attribute';
                                        }
                                        if (strpos($attr_lower, "профессия") !== false) {
                                            $tag_class .=' profession';
                                        }

                                  ?>
                                      <span class="<?php echo $tag_class; ?>"><?php echo htmlspecialchars($attr); ?></span>
                                  <?php endforeach; ?>
                              <?php endif; ?>
                          </div>
                      </div>
                  </a>
              <?php endforeach; ?>
          <?php endif; ?>
        </div>
    </div>
  </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const programGridContainer = document.getElementById('programGridContainer');
            const searchInput = document.getElementById('programSearchInput');

            function filterPrograms(searchTerm) {
                const term = searchTerm.toLowerCase().trim();
                const items = programGridContainer.querySelectorAll('.program-card');
                let visibleItemsCount = 0;

                items.forEach(item => {
                    const text = item.dataset.searchText;
                    const matches = !term || text.includes(term);
                    item.style.display = matches ? 'flex' : 'none';
                    if (matches) {
                        visibleItemsCount++;
                    }
                });

                let jsNoResultsEl = programGridContainer.querySelector('.js-no-results-message');
                const phpMessageExists = !!programGridContainer.querySelector('.php-no-results-message');

                if (term && visibleItemsCount === 0 && !phpMessageExists) {
                    if (!jsNoResultsEl) {
                        jsNoResultsEl = document.createElement('p');
                        jsNoResultsEl.className = 'no-results js-no-results-message';
                        programGridContainer.appendChild(jsNoResultsEl);
                    }
                    jsNoResultsEl.textContent = 'По вашему запросу программы не найдены.';
                    jsNoResultsEl.style.display = 'block';
                } else if (jsNoResultsEl) {
                    jsNoResultsEl.style.display = 'none';
                }
            }

            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    filterPrograms(e.target.value);
                });
                if (searchInput.value) {
                    filterPrograms(searchInput.value);
                }
            }

            const backButton = document.getElementById('backButton');
            if (backButton) { backButton.addEventListener('click', () => { history.back(); }); }

            const goHomeButton = document.getElementById('goHomeButton');
            if (goHomeButton) { goHomeButton.addEventListener('click', () => { window.location.href = 'index.php'; }); }
        });
    </script>
</body>
</html>