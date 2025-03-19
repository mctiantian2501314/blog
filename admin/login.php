<?php
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === $GLOBALS['valid_username'] && $password === $GLOBALS['valid_password']) {
        $_SESSION['loggedin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = "用户名或密码错误";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>登录</title>
    <style>
        body { max-width: 400px; margin: 50px auto; padding: 20px; }
        .alert { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>后台登录</h1>
    <?php if(isset($error)): ?>
        <div class="alert"><?= $error ?></div>
    <?php endif; ?>
    <form method="post">
        <div>
            <label>用户名：
                <input type="text" name="username" required>
            </label>
        </div>
        <div>
            <label>密码：
                <input type="password" name="password" required>
            </label>
        </div>
        <button type="submit">登录</button>
    </form>
</body>
</html>