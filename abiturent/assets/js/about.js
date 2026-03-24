// Ждем полной загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    
    // Получаем элементы модального окна
    const aboutBtn = document.getElementById('aboutSiteButton');
    const modal = document.getElementById('aboutModal');
    const closeBtn = document.querySelector('.close');
    const goFurtherBtn = document.getElementById('goFurtherBtn');
    
    // Получаем все кнопки вкладок
    const buttons = {
        directions: document.getElementById('directionsAndProgramsButton'),
        admission: document.getElementById('admissionWithTwoOGEButton'),
        professionalitet: document.getElementById('professionalitetButton'),
        establishments: document.getElementById('establishmentsButton')
    };
    
    // Функция для открытия модального окна
    function openModal() {
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Блокируем прокрутку страницы
        }
    }
    
    // Функция для закрытия модального окна
    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Возвращаем прокрутку
        }
    }
    
    // Функция для активации кнопки
    function activateButton(activeButton) {
        // Убираем класс active у всех кнопок
        Object.values(buttons).forEach(button => {
            if (button) {
                button.classList.remove('active');
            }
        });
        // Добавляем класс active выбранной кнопке
        if (activeButton) {
            activeButton.classList.add('active');
        }
    }
    
    // Функция для загрузки контента (пример)
    function loadContent(contentType) {
        console.log(`Загрузка контента: ${contentType}`);
        // Здесь можно добавить логику загрузки контента через AJAX/fetch
        // Например:
        /*
        fetch(`api/get-content.php?type=${contentType}`)
            .then(response => response.json())
            .then(data => {
                // Обновляем контент на странице
                document.getElementById('contentArea').innerHTML = data.html;
            });
        */
        
        // Временное решение - показываем уведомление
        showNotification(`Загружается раздел: ${contentType}`);
    }
    
    // Функция для показа уведомления
    function showNotification(message) {
        // Создаем элемент уведомления
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            z-index: 1001;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        // Удаляем уведомление через 3 секунды
        setTimeout(() => {
            notification.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    // Обработчики событий для кнопок меню
    if (buttons.directions) {
        buttons.directions.addEventListener('click', function() {
            activateButton(this);
            loadContent('directions');
        });
    }
    
    if (buttons.admission) {
        buttons.admission.addEventListener('click', function() {
            activateButton(this);
            loadContent('admission');
        });
    }
    
    if (buttons.professionalitet) {
        buttons.professionalitet.addEventListener('click', function() {
            activateButton(this);
            loadContent('professionalitet');
        });
    }
    
    if (buttons.establishments) {
        buttons.establishments.addEventListener('click', function() {
            activateButton(this);
            loadContent('establishments');
        });
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
            
            // Дополнительная логика при нажатии "Перейти далее"
            showNotification('Добро пожаловать! Начинаем исследование сайта.');
            
            // Можно активировать первую вкладку
            if (buttons.directions) {
                setTimeout(() => {
                    buttons.directions.click();
                }, 300);
            }
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
    
    // Добавляем анимацию fadeOut для уведомлений
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100px);
            }
        }
    `;
    document.head.appendChild(style);
    
    console.log('Сайт загружен и готов к работе!');
});