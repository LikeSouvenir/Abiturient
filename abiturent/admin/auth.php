<?php
/**
 * Обработка входа в систему
 */
function handleLogin($conn, $current_tab) {
    if (!isset($_POST['admin_login'])) {
        return $current_tab;
    }
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    error_log("Login attempt - Username: " . $username);
    error_log("Login attempt - Password from form: " . $password);
    
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        error_log("Stored hash: " . $user['password']);
        error_log("Password from form: " . $password);
        
        if ($user['password'] === $password) {
            $_SESSION['admin_loggedin'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            
            error_log("Login successful for user: " . $username);
            
            header("Location: index.php?tab=" . ($current_tab == 'login' ? 'directions' : $current_tab));
            exit;
        } else {
            error_log("Password mismatch for user: " . $username);
            $_SESSION['login_error'] = "Неверный логин или пароль.";
        }
    } else {
        error_log("User not found: " . $username);
        $_SESSION['login_error'] = "Неверный логин или пароль.";
    }
    
    $stmt->close();
    header("Location: index.php?tab=login");
    exit;
}

/**
 * Обработка выхода из системы
 */
function handleLogout($current_tab) {
    if ($current_tab == 'logout') {
        $_SESSION = array();
        session_destroy();
        header("Location: index.php?tab=login");
        exit;
    }
}

/**
 * Проверка авторизации
 */
function checkAuth($current_tab) {
    if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
        if ($current_tab !== 'login') {
            header("Location: index.php?tab=login");
            exit;
        }
    }
}
?>