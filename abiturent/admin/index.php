<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'directions';
$GLOBALS['current_tab'] = $current_tab;

$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : null;
unset($_SESSION['message']);
unset($_SESSION['message_type']);

$error_message = '';

// Обработка авторизации
handleLogin($conn, $current_tab);

// Обработка выхода
handleLogout($current_tab);

// Проверка авторизации
checkAuth($current_tab);

include 'header.php';

if ($current_tab == 'login') {
    include 'tabs/login.php';
} else {
    ?>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Панель Администратора</h1>
             <div class="user-info"> 
                <span>Привет, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</span>
                <a href="index.php?tab=logout" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.9em;">Выход</a>
            </div>
        </div>

        <?php include 'partials/navigation.php'; ?>
        <?php include 'partials/messages.php'; ?>

        <?php
        switch($current_tab) {
            case 'directions':
                include 'tabs/directions.php';
                break;
            case 'programs':
                include 'tabs/programs.php';
                break;
            case 'establishments':
                include 'tabs/establishments.php';
                break;
            case 'clusters':
                include 'tabs/clusters.php';
                break;
            case 'bundles':
                include 'tabs/bundles.php';
                break;
            default:
                include 'tabs/directions.php';
        }
        ?>
    </div>
    <?php
}

include 'footer.php';
?>