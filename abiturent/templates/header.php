<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ИС "Абитуриент" - <?= htmlspecialchars($page_title) ?></title>
    <script src="https://api-maps.yandex.ru/2.1/?apikey=YOUR_YANDEX_MAPS_API_KEY&lang=ru_RU" type="text/javascript"></script>
    <link rel="stylesheet" href="assets/css/style.css">
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
            <button class="header-button filter-button" id="goHomeButtonEst" title="На главную">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20px" height="20px">
                    <path d="M12 3.198l-10 9.143V22h5V14h6v8h5V12.34L12 3.198z"/>
                </svg>
            </button>
        </div>
    </header>