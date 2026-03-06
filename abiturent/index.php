<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/admin/config.php'; 

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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ИС "Абитуриент"</title>
  <link rel="icon" href="favicon.ico">
  <style>
    body {
      font-family: sans-serif;
      background-color: #f5f6fa;
      margin: 0;
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
   .attribute-tag {
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

    .container {
      padding: 0;
      max-width: 1200px;
      margin: auto;
      width: 100%;
    }
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
    .top-buttons {
      display: flex;
      justify-content: center;
      margin-bottom: 20px;
      gap: 10px;
      flex-wrap: wrap;
    }
    .top-buttons button {
      margin: 0;
      padding: 10px 18px;
      font-size: 0.95rem;
      border: 1px solid #bdc3c7;
      border-radius: 5px;
      background-color: #ecf0f1;
      color: #2c3e50;
      cursor: pointer;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
      transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
    }
     .top-buttons button:hover, .top-buttons button.active {
         background-color: #3498db;
         color: white;
         border-color: #2980b9;
     }
    .footer {
      text-align: right;
      padding: 1rem;
      font-size: 0.8rem;
      color: #7f8c8d;
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
        .card {
            width: calc(50% - 10px);
        }
     }
     @media (max-width: 768px) {
         body { padding: 10px; }
         .card {
             width: 100%;
             min-width: auto;
         }
         .header, .container {
             padding-left: 0;
             padding-right: 0;
         }
         .header {
             flex-wrap: wrap;
         }
         .search-area {
             width: 100%;
             margin-right: 0;
             margin-bottom: 10px;
             order: 1;
        }
        .header-button.back-button {
             display: none;
        }
        .header-button.filter-button {
             order: 2;
             margin-left: auto;
        }
          .top-buttons {
              flex-direction: column;
              align-items: stretch;
              gap: 8px;
          }
     }
    .loading, .no-results {
        text-align: center;
        padding: 20px;
        font-size: 1.2em;
        color: #555;
        width: 100%;
    }
  </style>
</head>
<body>
  <header class="header">
        <div class="search-area">
            <div class="search-container">
                 <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20px" height="20px">
                    <path d="M10 2a8 8 0 0 1 6.32 12.9L20.69 18.3a1 1 0 0 1-1.41 1.41l-4.39-4.38A8 8 0 1 1 10 2zm0 2a6 6 0 1 0 0 12A6 6 0 0 0 10 4z"/>
                </svg>
                <input type="text" class="search-input" id="mainSearchInput" placeholder="Поиск по направлениям, программам и кластерам...">
            </div>
             <button class="header-button filter-button" title="Фильтры (не реализовано)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20px" height="20px">
                   <path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/>
                </svg>
            </button>
        </div>
  </header>
  <div class="container">
    <div class="top-buttons">
      <button id="directionsAndProgramsButton" class="active">Направления и программы</button>
      <button id="admissionWithTwoOGEButton">Поступление с 2 ОГЭ</button>
      <button id="professionalitetButton">Профессионалитет</button>
      <button id="establishmentsButton">Учебные заведения</button>

    </div>
    <h1 id="mainTitle">ВЫБЕРИТЕ НАПРАВЛЕНИЕ</h1>
    <div class="grid" id="dataGrid">
      <?php if (empty($directions) && empty($programs) && empty($clusters)): ?>
          <div class="loading">Загрузка данных...</div>
      <?php endif; ?>
      </div>
  </div>
   <script>
    document.addEventListener('DOMContentLoaded', () => {
        const dataGrid = document.getElementById('dataGrid');
        const searchInput = document.getElementById('mainSearchInput');
        const mainTitle = document.getElementById('mainTitle');
        const directionsBtn = document.getElementById('directionsAndProgramsButton');
        const twoOgeBtn = document.getElementById('admissionWithTwoOGEButton');
        const professionalitetBtn = document.getElementById('professionalitetButton');
        const establishmentsButton = document.getElementById('establishmentsButton');
        const topButtons = [directionsBtn, twoOgeBtn, professionalitetBtn, establishmentsButton];



        const allDirectionsData = <?php echo json_encode($directions, $json_options); ?>;
        const allProgramsData = <?php echo json_encode($programs, $json_options); ?>;
        const allClustersData = <?php echo json_encode($clusters, $json_options); ?>;
        const allEstablishmentsData = <?php echo json_encode($establishments, $json_options); ?>;

        function setActiveButton(activeBtn) {
            topButtons.forEach(btn => btn.classList.remove('active'));
            if (activeBtn) {
                activeBtn.classList.add('active');
            }
        }

        function renderCard(item, type) {
            const card = document.createElement('a');
            card.className = 'card';
            let searchText = '';
            let imgPath = '';
            let textHtml = '';
            let titleText = '';

            if (type === 'direction') {
                card.href = `programs.php?direction_code=${encodeURIComponent(item.program_code_identifier)}`;
                titleText = item.name.toUpperCase();
                searchText = `${item.name} ${item.program_code_identifier}`.toLowerCase();
                imgPath = item.image_path;
                textHtml = `<span class="title">${titleText}</span><small>Укрупненная группа: ${item.program_code_identifier}</small>`;
            } else if (type === 'program') {
                card.href = `establishments.php?program_code=${encodeURIComponent(item.program_code)}`;
                titleText = item.name.toUpperCase();
                searchText = `${item.name || ''} ${item.program_code || ''} ${item.keywords || ''} ${(item.attributes_array || []).join(' ')}`.toLowerCase();
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
                searchText = `${item.name || ''}`.toLowerCase();
                imgPath = item.image_path;
                textHtml = `<span class="title">${titleText}</span>`;
            } else if ( type === 'establishments') {
                card.href = `establishment-programs.php?establishment_id=${item.id}`;
                titleText = item.name.toUpperCase();
                searchText = `${item.name || ''}`.toLowerCase();
                imgPath = item.image_path;
                textHtml = `<span class="title">${titleText}</span>`;
            }


            card.setAttribute('data-search-text', searchText);
            card.setAttribute('data-type', type);

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

        function showNoResults(message = "Ничего не найдено.") {
            dataGrid.innerHTML = `<p class="no-results">${message}</p>`;
        }

        function displayInitialDirections() {
            mainTitle.textContent = 'ВЫБЕРИТЕ НАПРАВЛЕНИЕ';
            dataGrid.innerHTML = '';
            if (allDirectionsData.length === 0) {
                 showNoResults('Направления не найдены.');
            } else {
                displayItems(allDirectionsData, 'direction');
            }
            setActiveButton(directionsBtn);
        }

         function displayProgramsWithTwoOGE() {
             mainTitle.textContent = 'ПРОГРАММЫ С ПОСТУПЛЕНИЕМ ПО 2 ОГЭ';
             dataGrid.innerHTML = '';
             const filteredPrograms = allProgramsData.filter(program =>
                program.attributes_array && program.attributes_array.some(attr => attr.toLowerCase().includes('2 огэ'))
             );
             if (filteredPrograms.length === 0) {
                 showNoResults('Программы с поступлением по 2 ОГЭ не найдены.');
             } else {
                 displayItems(filteredPrograms, 'program');
             }
             setActiveButton(twoOgeBtn);
         }

         function displayClusters() {
            mainTitle.textContent = 'КЛАСТЕРЫ ПРОФЕССИОНАЛИТЕТА';
            dataGrid.innerHTML = '';
            if (!allClustersData || allClustersData.length === 0) {
                showNoResults('Кластеры профессионалитета не найдены.');
            } else {
                displayItems(allClustersData, 'cluster');
            }
            setActiveButton(professionalitetBtn);
         }

        function displayEstablishments() {
            mainTitle.textContent = "Учебные заведения";
            dataGrid.innerHTML="";
            if (!allEstablishmentsData || allEstablishmentsData.length === 0) {
                showNoResults('Кластеры профессионалитета не найдены.');
            } else {
                displayItems(allEstablishmentsData, 'establishments');
            }
            setActiveButton(establishmentsButton);
        }

        function displaySearchResults(searchTerm) {
            const term = searchTerm.toLowerCase().trim();
            mainTitle.textContent = 'РЕЗУЛЬТАТЫ ПОИСКА';
            dataGrid.innerHTML = '';
            setActiveButton(null);

            if (!term) {
                displayInitialDirections();
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
                showNoResults('Ничего не найдено по вашему запросу.');
            }
        }

        if (allDirectionsData.length > 0 || allProgramsData.length > 0 || allClustersData.length > 0) {
             displayInitialDirections();
        } else {

            const initialLoadingMessage = document.querySelector('.loading');
            if (initialLoadingMessage) {
                initialLoadingMessage.textContent = "Данные не загружены или отсутствуют.";
            } else if (dataGrid.innerHTML.trim() === '') { 
                 dataGrid.innerHTML = `<div class="no-results">Данные не загружены или отсутствуют.</div>`;
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                displaySearchResults(e.target.value);
            });
        }

        if(directionsBtn) {
            directionsBtn.addEventListener('click', () => {
                searchInput.value = '';
                displayInitialDirections();
            });
        }
        if(twoOgeBtn) {
            twoOgeBtn.addEventListener('click', () => {
                 searchInput.value = '';
                 displayProgramsWithTwoOGE();
            });
        }
        if(professionalitetBtn) {
            professionalitetBtn.addEventListener('click', () => {
                 searchInput.value = '';
                 displayClusters();
            });
        }
        if(establishmentsButton) {
            establishmentsButton.addEventListener('click', () => {
                 searchInput.value = '';
                 displayEstablishments();
            });
        }
    });
   </script>
</body>
</html>