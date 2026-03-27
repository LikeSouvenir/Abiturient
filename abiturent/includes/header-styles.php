<?php
// Файл: includes/header-styles.php
// Общие стили для всего сайта
?>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: Arial, sans-serif;
        background-color: #f5f5f5;
        min-height: 100vh;
    }
    
    /* Стили для хедера */
    .header {
        background: #fff;
        padding: 15px 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 100;
    }
    
    .search-area {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .search-container {
        flex: 1;
        display: flex;
        align-items: center;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 8px 15px;
        background: #f9f9f9;
    }
    
    .search-container svg {
        margin-right: 10px;
        color: #666;
    }
    
    .search-input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        font-size: 16px;
    }
    
    .header-button {
        background: none;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 8px 15px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .header-button:hover {
        background: #f0f0f0;
    }
    
    /* Контейнер для навигационных кнопок */
    .nav-container {
        background: #fff;
        border-bottom: 1px solid #eee;
        position: sticky;
        top: 70px;
        z-index: 99;
    }
    
    .top-buttons {
        max-width: 1200px;
        margin: 0 auto;
        padding: 15px 20px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .top-buttons button {
        padding: 10px 20px;
        border: none;
        background: none;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 14px;
        color: #333;
        position: relative;
    }
    
    .top-buttons button:hover {
        color: #007bff;
    }
    
    .top-buttons button.active {
        color: #007bff;
        font-weight: bold;
    }
    
    .top-buttons button.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: #007bff;
    }
    
    /* Стили для основного контента */
    main {
        max-width: 1200px;
        margin: 20px auto;
        padding: 0 20px;
    }
    
    .container {
        max-width: 1200px;
        margin: 20px auto;
        padding: 0 20px;
    }
    
    /* Стили для модального окна */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 0;
        width: 90%;
        max-width: 800px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        animation: modalSlideIn 0.3s;
    }
    
    @keyframes modalSlideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .close {
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        padding: 15px 20px;
    }
    
    .close:hover {
        color: #007bff;
    }
    
    .modal-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
    }
    
    .modal-header h2 {
        margin: 0;
    }
    
    .modal-body {
        padding: 20px;
        max-height: 60vh;
        overflow-y: auto;
    }
    
    .modal-body p {
        margin-bottom: 15px;
        line-height: 1.6;
    }
    
    .modal-link {
        color: #007bff;
        text-decoration: none;
    }
    
    .modal-link:hover {
        text-decoration: underline;
    }
    
    .important-note {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px;
        margin: 20px 0;
        border-radius: 5px;
    }
    
    /* Стили для страницы establishments.php */
    .left-column {
        flex: 1;
    }
    
    .link-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .establishment-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        text-decoration: none;
        color: inherit;
        display: flex;
        gap: 20px;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .establishment-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .establishment-logo {
        width: 80px;
        height: 80px;
        object-fit: contain;
    }
    
    .establishment-info {
        flex: 1;
    }
    
    .establishment-info h3 {
        margin-bottom: 10px;
        color: #2c3e50;
    }
    
    .establishment-address {
        color: #666;
        margin-bottom: 10px;
    }
    
    .program-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }
    
    .tag {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        background: #e9ecef;
    }
    
    .tag-two-oge {
        background: #ffc107;
        color: #856404;
    }
    
    .tag-professionalitet {
        background: #17a2b8;
        color: white;
    }
    
    /* Стили для страницы programs.php */
    .program-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
    }
    
    .program-card {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: flex;
    }
    
    .program-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .program-card img {
        width: 100px;
        height: 100px;
        object-fit: cover;
    }
    
    .program-details {
        padding: 15px;
        flex: 1;
    }
    
    .program-details h3 {
        margin-bottom: 8px;
        font-size: 16px;
    }
    
    .program-details p {
        margin-bottom: 5px;
        color: #666;
        font-size: 14px;
    }
    
    .tags {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-top: 8px;
    }
    
    .title {
        margin-bottom: 20px;
        color: #2c3e50;
    }
    
    .no-results {
        text-align: center;
        padding: 40px;
        color: #666;
        font-size: 18px;
    }
    
    /* Стили для карты */
    .right-column {
        width: 400px;
        position: sticky;
        top: 150px;
        height: 500px;
    }
    
    #map {
        width: 100%;
        height: 100%;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* Адаптивность */
    @media (max-width: 768px) {
        .top-buttons {
            justify-content: center;
        }
        
        .program-grid {
            grid-template-columns: 1fr;
        }
        
        .establishment-card {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .modal-content {
            width: 95%;
            margin: 10% auto;
        }
        
        .right-column {
            width: 100%;
            position: static;
            height: 400px;
            margin-top: 20px;
        }
    }
</style>