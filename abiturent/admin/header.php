<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        .admin-container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .admin-header h1 { margin: 0; font-size: 1.8em; }
        .admin-nav ul { list-style-type: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; }
        .admin-nav li { margin-right: 10px; margin-bottom: 5px;}
        .admin-nav a { text-decoration: none; color: #007bff; font-weight: bold; padding: 5px 10px; border-radius: 4px; display: inline-block; }
        .admin-nav a:hover, .admin-nav a.active { background-color: #007bff; color: #fff; }
        .content-section { margin-bottom: 30px; }
        .content-section h2 { border-bottom: 2px solid #007bff; padding-bottom: 5px; margin-bottom: 15px; font-size: 1.5em; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 0.9em; }
        table th, table td { border: 1px solid #ddd; padding: 6px 10px; text-align: left; vertical-align: top;}
        table th { background-color: #f0f0f0; }
        table img.thumbnail { max-width: 50px; max-height: 50px; border-radius: 4px; }
        .action-links form { display: inline-block; margin-right: 5px; }
        .action-links a, .action-links button { display: inline-block; margin-right: 5px; color: #007bff; text-decoration: none; cursor: pointer; padding: 3px 6px; border-radius:3px; font-size:0.9em }
        .action-links button { background: none; border: 1px solid; color: red; }
        .action-links a { border: 1px solid #007bff;}
        .action-links a:hover { background-color:#007bff; color:white;}
        .action-links button:hover { background-color:red; color:white;}


        .form-container { background-color: #f9f9f9; padding: 20px; border-radius: 5px; border: 1px solid #eee; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="file"],
        .form-group textarea,
        .form-group select {
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .btn { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; }
        .btn-danger { background-color: #dc3545; }
        .btn-primary { background-color: #007bff; }
        .btn:hover { opacity: 0.9; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .login-container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 350px; margin: 50px auto; }
        .login-container h2 { text-align: center; margin-top: 0; margin-bottom: 20px; }
        .login-container .btn { width: 100%; }
        .error-text { color: red; margin-bottom: 10px; }
        #map-placeholder { width: 100%; height: 300px; background-color: #e9e9e9; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; text-align: center; color: #777; margin-top: 10px; border-radius: 4px; }
        #map-placeholder-bundle { width: 100%; height: 300px; background-color: #e9e9e9; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; text-align: center; color: #777; margin-top: 10px; border-radius: 4px; }
        .current-image-admin { max-width: 100px; max-height: 100px; margin-top: 10px; display: block; border:1px solid #ddd; padding:2px; border-radius:4px; }
        .form-group input[type="checkbox"] { width: auto; margin-right: 5px; vertical-align: middle;}
        .form-group label.checkbox-label { font-weight: normal; display:inline; }

        .program-selection-group, .attribute-selection-group {
             border: 1px solid #ccc;
             padding: 10px;
             border-radius: 4px;
             max-height: 150px;
             overflow-y: auto;
             background-color: white;
         }
        .program-selection-group label, .attribute-selection-group label {
             display: block;
             font-weight: normal;
             margin-bottom: 5px;
         }
         .program-selection-group input[type="checkbox"],
         .attribute-selection-group input[type="checkbox"] {
             width: auto;
             margin-right: 8px;
         }
         .admin-nav .user-info { margin-left: auto; padding: 5px 10px; text-align:right; }
         .admin-nav .user-info span { margin-right: 10px; }
    </style>
    <?php if (in_array($GLOBALS['current_tab'], ['establishments', 'bundles'])): ?>
       <script src="https://api-maps.yandex.ru/2.1/?apikey=45e028ca-92c7-4576-9119-12e906d9c092&lang=ru_RU" type="text/javascript"></script>
    <?php endif; ?>
</head>
<body>