<?php
// admin/config.php
require_once 'auth.php';
check_login();

$config_json_file = '../blog_config.json';
$config_index_html_file = '../index.html';
$config_json_php_file = '../json.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config_json_content = $_POST['config_json'];
    $config_index_html_content = $_POST['config_index_html'];
    $config_json_php_content = $_POST['config_json_php'];

    // 保存JSON配置文件的草稿
    file_put_contents($config_json_file.'.bak', $config_json_content);
    // 保存HTML配置文件的草稿
    file_put_contents($config_index_html_file.'.bak', $config_index_html_content);
    // 保存PHP配置文件的草稿
    file_put_contents($config_json_php_file.'.bak', $config_json_php_content);

    if (isset($_POST['publish'])) {
        // 发布JSON配置文件
        rename($config_json_file.'.bak', $config_json_file);
        // 发布HTML配置文件
        rename($config_index_html_file.'.bak', $config_index_html_file);
        // 发布PHP配置文件
        rename($config_json_php_file.'.bak', $config_json_php_file);
    }
}

// 读取配置 
// json配置文件
$current_config_json = file_exists($config_json_file)? 
    file_get_contents($config_json_file) : 
    '{"generator": {}}';
// 读取配置php
$current_config_php = file_exists($config_json_php_file)? 
    file_get_contents($config_json_php_file) : 
    '{"generator": {}}';
// 读取配置站点html
$current_config_html = file_exists($config_index_html_file)? 
    file_get_contents($config_index_html_file) : 
    '{"generator": {}}';
?>
<!DOCTYPE html>
<html>
<head>
    <title>配置管理</title>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div style="padding:20px;">
        <h2>博客配置</h2>
        <link rel="stylesheet" href="../github-markdown.css">
        <link rel="stylesheet" href="../github.min.css">
        <form method="post" class="markdown-body">
            <div style="margin-bottom:10px;">
                <button type="submit">保存草稿</button>
                <button type="submit" name="publish">发布配置</button>
            </div>
            <h3>配置json</h3>
            <p>[内容开关 0/1]<p>
            <p>include_content </p>
            <p>这个参数是是否 正文内容保存在data.json 其他的不建议修改</p>
            <textarea name="config_json" style="width:100%;height:500px;font-family: monospace;"><?= htmlspecialchars($current_config_json) ?></textarea>
            
            <h3>配置html</h3>
            <p>可以改变网站首页 添加友情链接什么的...</p>
            <textarea name="config_index_html" style="width:100%;height:500px;font-family: monospace;"><?= htmlspecialchars($current_config_html) ?></textarea>
            
            <h3>配置php</h3>
            <p>改变生成规则，严重危险 不建议修改 ...</p>
            <p>目前不危险的操作就是
            <pre>
            if ($include_content) {
                //$post['content'] = $content;
            }
            </pre>把这段代码注释掉</p>
            <textarea name="config_json_php" style="width:100%;height:500px;font-family: monospace;"><?= htmlspecialchars($current_config_php) ?></textarea>
        </form>
    </div>
</body>
</html>
