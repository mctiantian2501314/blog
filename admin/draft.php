
<?php
require_once 'auth.php';
check_login();

// ================== 处理发布操作 ==================
if (isset($_GET['publish'])) {
    $draft_path = realpath(urldecode($_GET['publish']));
    $md_root = realpath('../md');
    
    // 安全验证
    if ($draft_path && 
        strpos($draft_path, $md_root) === 0 &&
        pathinfo($draft_path, PATHINFO_EXTENSION) === 'bak'
    ) {
        $publish_path = substr($draft_path, 0, -4); // 移除.bak
        
        // 重命名文件
        if (rename($draft_path, $publish_path)) {
            // 更新生成数据
            exec('php ../generate.php');
            $_SESSION['message'] = '发布成功';
        } else {
            $_SESSION['error'] = '发布失败，请检查权限';
        }
    }
    header("Location: draft.php");
    exit;
}

// 改进的草稿扫描函数
function get_drafts() {
    $drafts = [];
    $md_root = realpath('../md');
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($md_root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'bak') {
            $draft_path = $file->getPathname();
            $publish_path = substr($draft_path, 0, -4);
            
            $drafts[] = [
                'draft_path' => $draft_path,
                'publish_path' => $publish_path,
                'rel_path' => ltrim(str_replace($md_root, '', $publish_path), '/'),
                'size' => filesize($draft_path),
                'mtime' => date('Y-m-d H:i:s', $file->getMTime()),
                'exists' => file_exists($publish_path)
            ];
        }
    }
    
    // 按修改时间排序
    usort($drafts, function($a, $b) {
        return $b['mtime'] <=> $a['mtime'];
    });
    
    return $drafts;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>草稿箱</title>
    <style>
        /* 新增消息提示样式 */
        .alert {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        .draft-list { margin: 20px; }
        .draft-item { 
            padding: 15px; 
            border: 1px solid #eee;
            margin-bottom: 10px;
            background: <?= $draft['exists'] ? '#fff3cd' : '#f8f9fa' ?>;
        }
        .draft-meta { color: #666; font-size: 0.9em; }
        .alert-badge { 
            background: #dc3545; 
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="draft-list">
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['message'] ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?= $_SESSION['error'] ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <h2>草稿箱</h2>
        <?php foreach(get_drafts() as $draft): ?>
        <div class="draft-item">
            <?php if($draft['exists']): ?>
                <div class="alert-badge">已存在正式文件</div>
            <?php endif; ?>
            <div><strong><?= htmlspecialchars($draft['rel_path']) ?></strong></div>
            <div class="draft-meta">
                大小：<?= round($draft['size']/1024, 2) ?> KB |
                修改时间：<?= $draft['mtime'] ?>
            </div>
            <div style="margin-top:10px;">
                <a href="edit.php?path=<?= urlencode($draft['rel_path']) ?>" 
                   class="btn-edit">继续编辑</a>
                <a href="?publish=<?= urlencode($draft['draft_path']) ?>" 
                   onclick="return confirm('确定发布此草稿？')"
                   style="color:green;margin:0 10px;">发布</a>
                <a href="?delete=<?= urlencode($draft['draft_path']) ?>" 
                   onclick="return confirm('确定永久删除此草稿？')" 
                   class="btn-delete">删除</a>
                <?php if($draft['exists']): ?>
                <a href="../?path=<?= urlencode($draft['rel_path']) ?>" 
                   target="_blank" class="btn-preview">预览正式版</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>

