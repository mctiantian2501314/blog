<?php
require_once 'auth.php';
check_login();
?>
<!DOCTYPE html>
<html>
<head>
    <title>管理后台</title>

<body>
    <?php include 'nav.php'; ?>
    
    <div class="container">
        <div class="sidebar">
            <h3>快捷操作</h3>
            <button onclick="location.href='edit.php'">新建文章</button>
            <button onclick="location.href='backup.php'">立即备份</button>
        </div>

    </div>
</body>
</html>