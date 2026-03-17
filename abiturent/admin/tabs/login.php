<div class="login-container">
    <h2>Авторизация</h2>
    <?php
    $login_error_msg = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
    unset($_SESSION['login_error']);
    if (!empty($login_error_msg)): ?>
        <p class="error-text"><?php echo htmlspecialchars($login_error_msg); ?></p>
    <?php endif; ?>
    <form action="index.php?tab=login" method="post">
        <input type="hidden" name="admin_login" value="1">
        <div class="form-group">
            <label for="username">Логин</label>
            <input type="text" name="username" id="username" required>
        </div>
        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div class="form-group">
            <button type="submit" class="btn">Войти</button>
        </div>
    </form>
</div>