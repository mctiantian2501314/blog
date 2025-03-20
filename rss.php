<?php
// rss.php
header('Content-Type: application/rss+xml; charset=utf-8');
date_default_timezone_set('Asia/Shanghai');

// 引入依赖库
require_once __DIR__.'/libs/php/Parsedown/Parsedown.php';
require_once __DIR__.'/libs/php/htmlpurifier-4.18.0/library/HTMLPurifier.auto.php';

// 初始化 Markdown 解析器
$parsedown = new Parsedown();

// 初始化 HTML 净化器
$purifier_config = HTMLPurifier_Config::createDefault();
$purifier_config->set('HTML.Allowed', 'p,br,h1,h2,h3,h4,h5,h6,pre,code,blockquote,ul,ol,li,strong,em,a[href|title],img[src|alt],table,thead,tbody,tr,th,td');
$purifier_config->set('Attr.AllowedClasses', []);
$purifier = new HTMLPurifier($purifier_config);

// 加载数据文件
$data_file = __DIR__.'/data.json';
if (!file_exists($data_file)) {
    die('<?xml version="1.0"?><error>数据文件不存在</error>');
}

$data = json_decode(file_get_contents($data_file), true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['posts'])) {
    die('<?xml version="1.0"?><error>数据文件格式错误</error>');
}

// 网站基础信息
$site_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$rss_title = '天天的小站订阅';
$rss_description = '最新技术文章与教程';

// 时间转换函数
function to_rfc822($datetime) {
    try {
        $date = new DateTime($datetime, new DateTimeZone('Asia/Shanghai'));
        return $date->format(DateTime::RFC822);
    } catch (Exception $e) {
        return date(DATE_RFC822);
    }
}

// Markdown 内容渲染
function render_content($post_path) {
    global $parsedown, $purifier;
    
    // 安全验证路径
    $md_root = realpath(__DIR__.'/md');
    $full_path = realpath(__DIR__."/md/{$post_path}");
    
    if (!$full_path || strpos($full_path, $md_root) !== 0) {
        return '<p>内容不可用</p>';
    }

    // 读取原始内容
    $content = @file_get_contents($full_path);
    if (!$content) {
        return '<p>内容加载失败</p>';
    }

    // 解析并净化内容
    $html = $parsedown->text($content);
    return $purifier->purify($html);
}

ob_clean();
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
    <atom:link href="<?= htmlspecialchars($site_url.'/rss.php') ?>" rel="self" type="application/rss+xml" />
    <title><?= htmlspecialchars($rss_title) ?></title>
    <link><?= htmlspecialchars($site_url) ?></link>
    <description><?= htmlspecialchars($rss_description) ?></description>
    <language>zh-cn</language>
    <lastBuildDate><?= to_rfc822(date('Y-m-d H:i:s')) ?></lastBuildDate>
    <generator>Markdown Blog System v2.1</generator>

    <?php foreach ($data['posts'] as $post): ?>
    <item>
        <title><?= htmlspecialchars($post['title']) ?></title>
        <link><?= htmlspecialchars($site_url.'/?path='.urlencode($post['path'])) ?></link>
        <guid isPermaLink="true"><?= htmlspecialchars($site_url.'/?path='.urlencode($post['path'])) ?></guid>
        <pubDate><?= to_rfc822($post['created']) ?></pubDate>
        
        <description>
            <![CDATA[
            <div style="font-family: sans-serif; max-width: 800px; margin: 0 auto;">
                <h2 style="color: #2c3e50;"><?= htmlspecialchars($post['title']) ?></h2>
                <div style="color: #7f8c8d; font-size: 0.9em;">
                    <p>📅 <?= $post['created'] ?> 发布</p>
                    <p>🔄 <?= $post['modified'] ?> 更新</p>
                    <p>📂 <?= $post['category'] ?></p>
                    <?php if(!empty($post['tags'])): ?>
                    <p>🏷️ <?= implode(' ', array_map(function($tag) {
                        return '<span style="background: #ecf0f1; padding: 2px 5px; border-radius: 3px;">#'.htmlspecialchars($tag).'</span>';
                    }, $post['tags'])) ?></p>
                    <?php endif; ?>
                </div>
                <hr style="border: 0; border-top: 1px solid #eee;">
                <?= render_content($post['path']) ?>
            </div>
            ]]>
        </description>
        
        <content:encoded>
            <![CDATA[
            <?= render_content($post['path']) ?>
            ]]>
        </content:encoded>
        
    </item>
    <?php endforeach; ?>
</channel>
</rss>