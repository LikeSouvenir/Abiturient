// Файл: assets/js/navigation.js
document.addEventListener('DOMContentLoaded', function() {
    // Получаем элементы
    const aboutBtn = document.getElementById('aboutSiteButton');
    const modal = document.getElementById('aboutModal');
    const closeBtn = document.querySelector('.close');
    const searchInput = document.getElementById('mainSearchInput');
    const directionsBtn = document.getElementById('directionsAndProgramsButton');
    const twoOgeBtn = document.getElementById('admissionWithTwoOGEButton');
    const professionalitetBtn = document.getElementById('professionalitetButton');
    const establishmentsBtn = document.getElementById('establishmentsButton');
    
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
    
    // Функция очистки поиска
    function clearSearch() {
        if (searchInput) {
            searchInput.value = '';
        }
    }
    
    // Функция навигации
    function navigateTo(page) {
        clearSearch();
        
        // Получаем текущий путь
        const currentPath = window.location.pathname;
        
        // Проверяем, находимся ли мы на главной странице (index.php)
        const isOnMainPage = currentPath === '/' || 
                             currentPath === '/index.php' || 
                             currentPath.endsWith('/index.php');
        
        if (isOnMainPage && window.updateContent) {
            // На главной странице используем динамическое обновление
            if (page === 'directions') {
                window.updateContent('directions');
            } else if (page === 'twoOge') {
                window.updateContent('twoOge');
            } else if (page === 'clusters') {
                window.updateContent('clusters');
            } else if (page === 'establishments') {
                window.updateContent('establishments');
            }
        } else {
            // На других страницах делаем обычный переход
            if (page === 'directions') {
                window.location.href = 'index.php';
            } else if (page === 'twoOge') {
                window.location.href = 'index.php?section=twoOge';
            } else if (page === 'clusters') {
                window.location.href = 'index.php?section=clusters';
            } else if (page === 'establishments') {
                window.location.href = 'establishments.php';
            }
        }
    }
    
    // Обработчики кнопок навигации
    if (directionsBtn) {
        directionsBtn.addEventListener('click', function() {
            navigateTo('directions');
        });
    }
    
    if (twoOgeBtn) {
        twoOgeBtn.addEventListener('click', function() {
            navigateTo('twoOge');
        });
    }
    
    if (professionalitetBtn) {
        professionalitetBtn.addEventListener('click', function() {
            navigateTo('clusters');
        });
    }
    
    if (establishmentsBtn) {
        establishmentsBtn.addEventListener('click', function() {
            navigateTo('establishments');
        });
    }
    
    // Поиск
    if (searchInput) {
        // Если на главной странице и есть функция handleSearch
        if (window.handleSearch) {
            searchInput.addEventListener('input', function(e) {
                window.handleSearch(e.target.value);
            });
        } else {
            // На других страницах перенаправляем на главную с поиском
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const searchTerm = this.value.trim();
                    if (searchTerm) {
                        window.location.href = `index.php?search=${encodeURIComponent(searchTerm)}`;
                    }
                }
            });
        }
    }
    
    // Установка активной кнопки
    function setActiveButton() {
        const currentPath = window.location.pathname;
        const urlParams = new URLSearchParams(window.location.search);
        const section = urlParams.get('section');
        
        // Сбрасываем все активные классы
        [directionsBtn, twoOgeBtn, professionalitetBtn, establishmentsBtn].forEach(btn => {
            if (btn) btn.classList.remove('active');
        });
        
        // На главной странице
        if (currentPath === '/' || currentPath === '/index.php' || currentPath.endsWith('/index.php')) {
            if (section === 'twoOge') {
                if (twoOgeBtn) twoOgeBtn.classList.add('active');
            } else if (section === 'clusters') {
                if (professionalitetBtn) professionalitetBtn.classList.add('active');
            } else if (section === 'establishments') {
                if (establishmentsBtn) establishmentsBtn.classList.add('active');
            } else {
                // По умолчанию - направления
                if (directionsBtn) directionsBtn.classList.add('active');
            }
        } 
        // На странице establishments.php
        else if (currentPath.includes('establishments.php')) {
            if (establishmentsBtn) establishmentsBtn.classList.add('active');
        }
        // На странице programs.php
        else if (currentPath.includes('programs.php')) {
            if (directionsBtn) directionsBtn.classList.add('active');
        }
        // На странице cluster.php
        else if (currentPath.includes('cluster.php')) {
            if (professionalitetBtn) professionalitetBtn.classList.add('active');
        }
        // На странице establishment-programs.php
        else if (currentPath.includes('establishment-programs.php')) {
            if (establishmentsBtn) establishmentsBtn.classList.add('active');
        }
    }
    
    setActiveButton();
});