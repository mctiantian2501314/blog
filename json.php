<?php
/**
 * Markdown博客生成脚本
 * 使用方式：php json.php [内容开关 0/1]
 */

$DEFAULT_CONFIG = [
    'generator' => [
        'md_dir' => 'md',
        'output_file' => 'data.json',
        'include_content' => 0,
        'time_format' => 'Y-m-d H:i:s',
        'auto_parse_tags' => 1,
        'allowed_categories' => []
    ]
];

// 加载配置
function load_config() {
    global $DEFAULT_CONFIG;
    
    if (!file_exists('blog_config.json')) {
        file_put_contents('blog_config.json', json_encode($DEFAULT_CONFIG, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    $config = json_decode(file_get_contents('blog_config.json'), true);
    return array_replace_recursive($DEFAULT_CONFIG, $config);
}

// 主生成函数
function generate_blog_data() {
    $config = load_config()['generator'];
    $include_content = $config['include_content'];
    
    $data = [
        'config' => $config,
        'posts' => []
    ];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($config['md_dir'], FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'md') {
            $path = $file->getPathname();
   
        // 新增分类路径计算逻辑
        $root = realpath($config['md_dir']);
        $filePath = realpath($file->getPath());
        
        if ($filePath !== false && strpos($filePath, $root) === 0) {
            // 计算相对路径并标准化格式
            $relativePath = substr($filePath, strlen($root));
            $relativePath = ltrim($relativePath, DIRECTORY_SEPARATOR);
            $category = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
        } else {
            $category = ''; 
        }
        
       

            // 内容处理
            $content = $include_content ? file_get_contents($path) : '';
            $rel_path = substr($path, strlen($config['md_dir']) + 1);
            
            // 标签解析
            $tags = [];
            if ($config['auto_parse_tags'] && preg_match('/\[标签\]\s*([\s\S]+?)\s*\[\/标签\]/i', $content, $matches)) {
                $tags = array_map('trim', preg_split('/[,|]/', preg_replace('/\s+/', ' ', $matches[1])));
            }

            $post = [
                'title' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                'created' => date($config['time_format'], $file->getCTime()),
                'modified' => date($config['time_format'], $file->getMTime()),
                'category' => $category,
                'archive' => date("Y-m", $file->getCTime()),
                'path' => str_replace('\\', '/', $rel_path),
                'tags' => $tags
            ];

            if ($include_content) {
                //$post['content'] = $content;
            }

            $data['posts'][] = $post;
        }
    }
    // 排序并保存
    usort($data['posts'], fn($a, $b) => strtotime($b['created']) - strtotime($a['created']));
    file_put_contents($config['output_file'], json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo "生成成功！文章数：".count($data['posts'])."\n";
    echo "输出文件：{$config['output_file']}\n";
        echo "内容字段/或者标签：".($include_content ? '已启用' : '已禁用')."\n";
}

// 执行生成
try {
    generate_blog_data();
} catch (Exception $e) {
    die("生成失败：".$e->getMessage());
}