<?php
require_once 'auth.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 生成sitemap逻辑
    include '../json.php'; // 先更新数据
    $data = json_decode(file_get_contents('../data.json'), true);
    
    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>
    <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    
    foreach ($data['posts'] as $post) {
        $sitemap .= "
        <url>
            <loc>/?path={$post['path']}</loc>
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