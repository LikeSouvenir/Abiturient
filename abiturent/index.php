<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/admin/config.php'; 

// Получаем параметры из URL
$section = isset($_GET['section']) ? $_GET['section'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

$sql_directions = "
    SELECT d.id, d.name, d.program_code_identifier, d.image_path
    FROM directions d
    INNER JOIN programs p ON p.direction_id = d.id
    INNER JOIN bundles b ON p.id = b.program_id
    GROUP BY d.id, d.name, d.program_code_identifier, d.image_path
    HAVING COUNT(DISTINCT b.establishment_id) > 0
    ORDER BY d.name";
$directions_result = $conn->query($sql_directions);
$directions = $directions_result ? $directions_result->fetch_all(MYSQLI_ASSOC) : [];

foreach($directions as $i => $direction) {
     $directions[$i]['image_path'] = "uploads/directions/" . ($direction['image_path'] ?? 'placeholder.svg');
}

$sql_programs = "
    SELECT p.id, p.name, p.program_code, p.keywords, p.attributes, p.image_path,
           d.name AS direction_name, d.program_code_identifier AS parent_direction_code_identifier
    FROM programs p
    INNER JOIN directions d ON p.direction_id = d.id
    ORDER BY d.name, p.name";
$programs_result = $conn->query($sql_programs);
$programs = $programs_result ? $programs_result->fetch_all(MYSQLI_ASSOC) : [];

foreach($programs as $i => $program) {
    $programs[$i]['image_path'] = "uploads/programs/" . ($program['image_path'] ?? 'placeholder.svg');
    $programs[$i]['attributes_array'] = !empty($program['attributes']) ? array_map('trim', explode(',', $program['attributes'])) : [];
}

$sql_clusters = "SELECT id, name, image_path FROM clusters ORDER BY name";
$clusters_result = $conn->query($sql_clusters);
$clusters = $clusters_result ? $clusters_result->fetch_all(MYSQLI_ASSOC) : [];

foreach($clusters as $i => $cluster) {
    $clusters[$i]['image_path'] = "uploads/clusters/" . ($cluster['image_path'] ?? 'placeholder.svg');
}

$establishments = $conn->query("SELECT * FROM establishments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
foreach($establishments as $i => $establishment) {
    $establishments[$i]['image_path'] = "uploads/establishments/" . ($establishment['logo_path'] ?? 'placeholder.svg');
}

$conn->close(); 
$json_options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;

$page_title = 'ИС "Абитуриент" - Главная';
include __DIR__ . '/templates/header.php';
?>

<style>
    /* Дополнительные стили только для этой страницы */
    h1 {
        text-align: center;
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 20px;
    }
    .grid {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: left;
    }
    .grid-section-header {
        width: 100%;
        text-align: center;
        font-size: 1.5em;
        color: #34495e;
        margin-bottom: 15px;
        margin-top: 20px;
    }
    .grid > .grid-section-header:first-of-type {
        margin-top: 0;
    }
    .card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 15px;
        display: flex;
        align-items: center;
        gap: 15px;
        text-decoration: none;
        color: inherit;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        width: calc(33.333% - 14px);
        min-width: 300px;
        box-sizing: border-box;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .card img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 4px;
        flex-shrink: 0;
    }
    .card .text {
        font-size: 1rem;
        flex-grow: 1;
    }
    .card .text .title {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }
    .card .text small {
        display: block;
        margin-top: 3px;
        color: #555;
        font-size: 0.85rem;
    }
    .no-results {
        text-align: center;
        padding: 20px;
        font-size: 1.2em;
        color: #555;
        width: 100%;
    }
    @media (max-width: 992px) {
        .card {
            width: calc(50% - 10px);
        }
    }
    @media (max-width: 768px) {
        .card {
            width: 100%;
            min-width: auto;
        }
    }
</style>

<h1 id="mainTitle">ВЫБЕРИТЕ НАПРАВЛЕНИЕ</h1>
<div class="grid" id="dataGrid">
    <?php if (empty($directions) && empty($programs) && empty($clusters)): ?>
        <div class="loading">Загрузка данных...</div>
    <?php endif; ?>
</div>

<script>
// Данные для страницы
const allDirectionsData = <?php echo json_encode($directions, $json_options); ?>;
const allProgramsData = <?php echo json_encode($programs, $json_options); ?>;
const allClustersData = <?php echo json_encode($clusters, $json_options); ?>;
const allEstablishmentsData = <?php echo json_encode($establishments, $json_options); ?>;

// Функции для работы с контентом
function updateContent(type) {
    const dataGrid = document.getElementById('dataGrid');
    const mainTitle = document.getElementById('mainTitle');
    
    if (type === 'directions') {
        mainTitle.textContent = 'ВЫБЕРИТЕ НАПРАВЛЕНИЕ';
        dataGrid.innerHTML = '';
        if (allDirectionsData.length === 0) {
            dataGrid.innerHTML = '<p class="no-results">Направления не найдены.</p>';
        } else {
            displayItems(allDirectionsData, 'direction');
        }
    } else if (type === 'twoOge') {
        mainTitle.textContent = 'ПРОГРАММЫ С ПОСТУПЛЕНИЕМ ПО 2 ОГЭ';
        dataGrid.innerHTML = '';
        const filteredPrograms = allProgramsData.filter(program =>
            program.attributes_array && program.attributes_array.some(attr => attr.toLowerCase().includes('2 огэ'))
        );
        if (filteredPrograms.length === 0) {
            dataGrid.innerHTML = '<p class="no-results">Программы с поступлением по 2 ОГЭ не найдены.</p>';
        } else {
            displayItems(filteredPrograms, 'program');
        }
    } else if (type === 'clusters') {
        mainTitle.textContent = 'КЛАСТЕРЫ ПРОФЕССИОНАЛИТЕТА';
        dataGrid.innerHTML = '';
        if (!allClustersData || allClustersData.length === 0) {
            dataGrid.innerHTML = '<p class="no-results">Кластеры профессионалитета не найдены.</p>';
        } else {
            displayItems(allClustersData, 'cluster');
        }
    } else if (type === 'establishments') {
        mainTitle.textContent = "УЧЕБНЫЕ ЗАВЕДЕНИЯ";
        dataGrid.innerHTML = "";
        if (!allEstablishmentsData || allEstablishmentsData.length === 0) {
            dataGrid.innerHTML = '<p class="no-results">Учебные заведения не найдены.</p>';
        } else {
            displayItems(allEstablishmentsData, 'establishments');
        }
    }
}

function renderCard(item, type) {
    const card = document.createElement('a');
    card.className = 'card';
    let imgPath = '';
    let textHtml = '';
    let titleText = '';
    
    if (type === 'direction') {
        card.href = `programs.php?direction_code=${encodeURIComponent(item.program_code_identifier)}`;
        titleText = item.name.toUpperCase();
        imgPath = item.image_path;
        textHtml = `<span class="title">${titleText}</span><small>Укрупненная группа: ${item.program_code_identifier}</small>`;
    } else if (type === 'program') {
        card.href = `establishments.php?program_code=${encodeURIComponent(item.program_code)}`;
        titleText = item.name.toUpperCase();
        imgPath = item.image_path;
        textHtml = `<span class="title">${titleText}</span><small>Код программы: ${item.program_code}</small>`;
        if (item.direction_name) {
            textHtml += `<small>Направление: ${item.direction_name}</small>`;
        }
        if (item.attributes_array && item.attributes_array.length > 0) {
            item.attributes_array.forEach(attribute => {
                textHtml += `<div class='attribute-tag'>${attribute}</div>`;
            });
        }
    } else if (type === 'cluster') {
        card.href = `cluster.php?cluster_id=${item.id}`;
        titleText = item.name.toUpperCase();
        imgPath = item.image_path;
        textHtml = `<span class="title">${titleText}</span>`;
    } else if (type === 'establishments') {
        card.href = `establishment-programs.php?establishment_id=${item.id}`;
        titleText = item.name.toUpperCase();
        imgPath = item.image_path;
        textHtml = `<span class="title">${titleText}</span>`;
    }
    
    const img = document.createElement('img');
    img.src = imgPath;
    img.alt = titleText;
    
    const textDiv = document.createElement('div');
    textDiv.className = 'text';
    textDiv.innerHTML = textHtml;
    
    card.appendChild(img);
    card.appendChild(textDiv);
    return card;
}

function displayItems(items, type, sectionHeaderText = null) {
    const dataGrid = document.getElementById('dataGrid');
    if (items && items.length > 0) {
        if (sectionHeaderText) {
            const header = document.createElement('h2');
            header.className = 'grid-section-header';
            header.textContent = sectionHeaderText;
            dataGrid.appendChild(header);
        }
        items.forEach(item => {
            dataGrid.appendChild(renderCard(item, type));
        });
    }
}

function handleSearch(searchTerm) {
    const term = searchTerm.toLowerCase().trim();
    const dataGrid = document.getElementById('dataGrid');
    const mainTitle = document.getElementById('mainTitle');
    mainTitle.textContent = 'РЕЗУЛЬТАТЫ ПОИСКА';
    dataGrid.innerHTML = '';
    
    if (!term) {
        updateContent('directions');
        return;
    }
    
    const filteredDirections = allDirectionsData.filter(direction =>
        `${direction.name || ''} ${direction.program_code_identifier || ''}`.toLowerCase().includes(term)
    );
    const filteredPrograms = allProgramsData.filter(program =>
        `${program.name || ''} ${program.program_code || ''} ${program.keywords || ''} ${(program.attributes_array || []).join(' ')}`.toLowerCase().includes(term)
    );
    const filteredClusters = allClustersData.filter(cluster =>
        `${cluster.name || ''}`.toLowerCase().includes(term)
    );
    const filteredEstablishments = allEstablishmentsData.filter(establishment =>
        `${establishment.name || ''}`.toLowerCase().includes(term)
    );
    
    let foundItems = false;
    if (filteredDirections.length > 0) {
        displayItems(filteredDirections, 'direction', 'Найденные направления:');
        foundItems = true;
    }
    if (filteredPrograms.length > 0) {
        displayItems(filteredPrograms, 'program', 'Найденные программы:');
        foundItems = true;
    }
    if (filteredClusters.length > 0) {
        displayItems(filteredClusters, 'cluster', 'Найденные кластеры:');
        foundItems = true;
    }
    if (filteredEstablishments.length > 0) {
        displayItems(filteredEstablishments, 'establishments', 'Найденные заведения:');
        foundItems = true;
    }
    
    if (!foundItems) {
        dataGrid.innerHTML = '<p class="no-results">Ничего не найдено по вашему запросу.</p>';
    }
}

// Делаем функции глобальными для доступа из header-nav.php
window.updateContent = updateContent;
window.handleSearch = handleSearch;

// Инициализация
document.addEventListener('DOMContentLoaded', () => {
    // Проверяем параметры URL
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section');
    const searchTerm = urlParams.get('search');
    
    if (searchTerm) {
        handleSearch(searchTerm);
    } else if (section === 'twoOge') {
        updateContent('twoOge');
    } else if (section === 'clusters') {
        updateContent('clusters');
    } else if (section === 'establishments') {
        updateContent('establishments');
    } else {
        updateContent('directions');
    }
});
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>