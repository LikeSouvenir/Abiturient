<?php
// Файл: templates/header.php
// Общий шаблон шапки для всех страниц
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'ИС "Абитуриент"'; ?></title>
    <link rel="icon" href="favicon.ico">
    <?php include __DIR__ . '/../includes/header-styles.php'; ?>
    <?php if (isset($additional_css)): ?>
        <link rel="stylesheet" href="<?php echo $additional_css; ?>">
    <?php endif; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/header-nav.php'; ?>