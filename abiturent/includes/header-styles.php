<?php
// Файл: includes/header-styles.php
// Общие стили для всех страниц
?>

<style>
    /* Общие стили для всех страниц */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: sans-serif;
        background-color: #f5f6fa;
        margin: 0;
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    /* Стили для хедера и навигации */
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
    
    /* Стили для модального окна */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.3s ease;
    }
    
    .modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 0;
        border-radius: 16px;
        width: 90%;
        max-width: 600px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        animation: slideIn 0.3s ease;
    }
    
    .modal-header {
        padding: 20px 25px;
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
        border-radius: 16px 16px 0 0;
        position: relative;
    }
    
    .modal-header h2 {
        margin: 0;
        font-size: 24px;
    }
    
    .modal-body {
        padding: 25px;
        color: #333;
        line-height: 1.6;
        max-height: 70vh;
        overflow-y: auto;
    }
    
    .modal-body p {
        margin-bottom: 15px;
    }
    
    .modal-body strong {
        color: #2c3e50;
    }
    
    .modal-footer {
        padding: 20px 25px;
        border-top: 1px solid #eee;
        text-align: right;
    }
    
    .close {
        color: #fff;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        transition: transform 0.2s ease;
        position: absolute;
        right: 20px;
        top: 15px;
    }
    
    .close:hover {
        transform: scale(1.1);
    }
    
    .go-further-btn {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .go-further-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
    }
    
    /* Стили для ссылки в модальном окне */
    .modal-link {
        color: #3498db;
        text-decoration: none;
        border-bottom: 1px solid transparent;
        transition: border-color 0.2s ease;
    }
    
    .modal-link:hover {
        border-bottom-color: #3498db;
    }
    
    /* Стили для важного примечания */
    .important-note {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 12px 15px;
        margin-top: 15px;
        border-radius: 4px;
    }
    
    .important-note strong {
        color: #856404;
        display: block;
        margin-bottom: 5px;
    }
    
    .important-note p {
        margin: 0;
        color: #856404;
    }
    
    /* Анимации */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    /* Адаптивность */
    @media (max-width: 768px) {
        body { padding: 10px; }
        .header {
            flex-wrap: wrap;
        }
        .search-area {
            width: 100%;
            margin-right: 0;
            margin-bottom: 10px;
            order: 1;
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
        .modal-content {
            width: 95%;
            margin: 10% auto;
        }
        .modal-body {
            padding: 20px;
        }
        .go-further-btn {
            width: 100%;
        }
    }
</style>