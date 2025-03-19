<?php
session_start();

// 硬编码登录凭证
$valid_username = 'admin';
$valid_password = 'admin';

// 登录检查函数
function check_login() {
    if (!isset($_SESSION['loggedin'])) {
        header('Location: login.php');
        exit;
    }
}

// 处理登出
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>