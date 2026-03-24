<?php
// Файл: includes/header-nav.php
// Панель навигации и модальное окно для всех страниц
?>

<header class="header">
    <div class="search-area">
        <div class="search-container">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20px" height="20px">
                <path d="M10 2a8 8 0 0 1 6.32 12.9L20.69 18.3a1 1 0 0 1-1.41 1.41l-4.39-4.38A8 8 0 1 1 10 2zm0 2a6 6 0 1 0 0 12A6 6 0 0 0 10 4z"/>
            </svg>
            <input type="text" class="search-input" id="mainSearchInput" placeholder="Поиск по направлениям и программам">
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
        <button id="aboutSiteButton">О сайте</button>
    </div>
</div>

<!-- Модальное окно -->
<div id="aboutModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="modal-header">
            <h2>О сайте</h2>
        </div>
        <div class="modal-body">
            <p><strong>Сайт разработан</strong> Колледжем электроники и информационных технологий имени Героя Российской Федерации В.К. Широкова, Колледжем судостроения, информационных и прикладных технологий и ГБУ ДПО ЦОПП СПб при поддержке Комитета по образованию.</p>
            
            <p>На сайте собрана актуальная информация о направлениях и образовательных программах, реализуемых в государственных учреждениях среднего профессионального образования Санкт-Петербурга.</p>
            
            <p>В каталоге представлены образовательные программы с очной формой обучения, финансируемые за счёт бюджетных средств. Коммерческие колледжи, а также колледжи при вузах в каталог не включены.</p>
            
            <p>Отдельно выделены программы, на которые возможно поступление по результатам двух ОГЭ (в рамках эксперимента по расширению доступности среднего профобразования), и программы, реализуемые в рамках Федерального проекта «Профессионалитет».</p>
            
            <p><strong>Актуальность информации</strong><br>
            Информация на сайте представлена из официальных документов Комитета по образованию (<a href="https://k-obr.spb.ru/media/uploads/userfiles/2026/02/27/1449-%D1%80_%D0%BE%D1%82_19.12.2025..pdf" target="_blank" class="modal-link">Распоряжение Комитета по образованию от 19.12.2025 № 1449-р об утверждении КЦП на 2026/2027 учебный год</a>) и сайтов образовательных учреждений. Данные обновляются ежегодно.</p>
            
            <div class="important-note">
                <strong>Важно!</strong>
                <p>Информация на сайте носит справочный характер. Перед подачей документов рекомендуется уточнять условия поступления в выбранном колледже.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button id="goFurtherBtn" class="go-further-btn">Перейти далее →</button>
        </div>
    </div>
</div>

<script>
// JavaScript для работы навигации и модального окна
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, есть ли элементы на странице
    const aboutBtn = document.getElementById('aboutSiteButton');
    const modal = document.getElementById('aboutModal');
    const closeBtn = document.querySelector('.close');
    const goFurtherBtn = document.getElementById('goFurtherBtn');
    const searchInput = document.getElementById('mainSearchInput');
    const directionsBtn = document.getElementById('directionsAndProgramsButton');
    const twoOgeBtn = document.getElementById('admissionWithTwoOGEButton');
    const professionalitetBtn = document.getElementById('professionalitetButton');
    const establishmentsButton = document.getElementById('establishmentsButton');
    
    // Функции для модального окна
    function openModal() {
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
    
    // Обработчики для модального окна
    if (aboutBtn) {
        aboutBtn.addEventListener('click', openModal);
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    if (goFurtherBtn) {
        goFurtherBtn.addEventListener('click', function() {
            closeModal();
            // Плавная прокрутка к началу страницы
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Закрытие при клике вне модального окна
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });
    
    // Закрытие по клавише Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal && modal.style.display === 'block') {
            closeModal();
        }
    });
    
    // Функция для очистки поиска при навигации
    function clearSearchAndNavigate(callback) {
        if (searchInput) {
            searchInput.value = '';
        }
        if (callback && typeof callback === 'function') {
            callback();
        }
    }
    
    // Обработчики навигации (если есть на странице)
    if (directionsBtn && window.updateContent) {
        directionsBtn.addEventListener('click', function() {
            clearSearchAndNavigate(() => window.updateContent('directions'));
        });
    }
    
    if (twoOgeBtn && window.updateContent) {
        twoOgeBtn.addEventListener('click', function() {
            clearSearchAndNavigate(() => window.updateContent('twoOge'));
        });
    }
    
    if (professionalitetBtn && window.updateContent) {
        professionalitetBtn.addEventListener('click', function() {
            clearSearchAndNavigate(() => window.updateContent('professionalitet'));
        });
    }
    
    if (establishmentsButton && window.updateContent) {
        establishmentsButton.addEventListener('click', function() {
            clearSearchAndNavigate(() => window.updateContent('establishments'));
        });
    }
    
    // Если на странице есть поиск, добавляем обработчик
    if (searchInput && window.handleSearch) {
        searchInput.addEventListener('input', function(e) {
            if (window.handleSearch) {
                window.handleSearch(e.target.value);
            }
        });
    }
    
    // Устанавливаем активную кнопку в зависимости от текущей страницы
    function setActiveButtonByPage() {
        const currentPath = window.location.pathname;
        const buttons = {
            directionsBtn: currentPath === '/' || currentPath === '/index.php',
            twoOgeBtn: currentPath.includes('two-oge'),
            professionalitetBtn: currentPath.includes('professionalitet'),
            establishmentsBtn: currentPath.includes('establishments')
        };
        
        if (buttons.directionsBtn && directionsBtn) {
            directionsBtn.classList.add('active');
        } else if (buttons.twoOgeBtn && twoOgeBtn) {
            twoOgeBtn.classList.add('active');
        } else if (buttons.professionalitetBtn && professionalitetBtn) {
            professionalitetBtn.classList.add('active');
        } else if (buttons.establishmentsBtn && establishmentsButton) {
            establishmentsButton.classList.add('active');
        }
    }
    
    setActiveButtonByPage();
});
</script>