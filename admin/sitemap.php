<?php
require_once 'auth.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 通过 AJAX 调用 json.php 获取数据
    $jsonData = file_get_contents('json.php');
    $data = json_decode($jsonData, true);

    // 自动获取当前域名
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . $host;

    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>
    <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    
    foreach ($data['posts'] as $post) {
        $sitemap .= "
        <url>
            <loc>{$baseUrl}/?path={$post['path']}</loc>
            <lastmod>".date('Y-m-d', strtotime($post['modified']))."</lastmod>
        </url>";
    }
    
    $sitemap .= '</urlset>';
    file_put_contents('../sitemap.xml', $sitemap);
    $msg = "站点地图已生成";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>站点地图生成</title>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container">
        <form method="post">
            <button type="submit">生成站点地图</button>
            <?php if(isset($msg)): ?>
                <div><?= $msg ?></div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
