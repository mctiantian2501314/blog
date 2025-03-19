<?php
require_once 'auth.php';
check_login();

// 加载生成数据
function get_blog_data() {
    if (!file_exists('../data.json')) {
        return ['posts' => []];
    }
    return json_decode(file_get_contents('../data.json'), true);
}

// 处理发布操作
if (isset($_GET['publish'])) {
    $bak_file = '../md/'.$_GET['publish'].'.bak';
    $md_file = str_replace('.bak', '', $bak_file);
    
    if (file_exists($bak_file)) {
        rename($bak_file, $md_file);
        exec('php ../json.php');
    }
    header('Location: posts.php');
    exit;
}
//处理删除操作
if (isset($_GET['delete'])) {
    $file = '../md/'.$_GET['delete'];
    if (file_exists($file)) {
        unlink($file);
        if (file_exists($file.'.bak')) {
            unlink($file.'.bak');
        }
        exec('php ../json.php');
    }
    header("Location: posts.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>文章管理</title>
    <style>
        .post-table { width:100%; border-collapse: collapse; }
        .post-table th, .post-table td { padding:12px; border:1px solid #ddd; }
        .category-path { color:#666; font-size:0.9em; }
        .post-actions a { margin-right:15px; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div style="padding:20px;">
        <h2>文章列表
         <a href="edit.php"><button>新建文章</button></a>
          <a href="draft.php"><button>草稿箱</button></a>
        </h2>
        
        <table class="post-table">
            <thead>
                <tr>
                    <th>标题</th>
                    <th>分类</th>
                    <th>更新时间</th>
                    <th>归档</th>
                    <th>标签</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach(get_blog_data()['posts'] as $post): 
                    $has_draft = file_exists('../md/'.$post['path'].'.bak');
                ?>
                <tr>
                    <td><?= htmlspecialchars($post['title']) ?></td>
                    <td><?= $post['category'] ? htmlspecialchars($post['category']) : '未分类' ?></td>
                  <td><?= htmlspecialchars($post['created']) ?></td>
                  <td><?= htmlspecialchars($post['archive']) ?></td>
                  <td><?= implode(', ', array_map('htmlspecialchars', $post['tags'])) ?></td>
                    <td class="post-actions">
                        <a href="edit.php?path=<?= urlencode($post['path']) ?>">编辑</a>
                        <a href="../?path=<?= urlencode($post['path']) ?>" target="_blank">预览</a>
                          <?php if($has_draft): ?>
                            <a href="?publish=<?= urlencode($post['path']) ?>" style="color:green">发布草稿</a>                                        <?php endif; ?>
                        <a href="?delete=<?= urlencode($post['path']) ?>" 
       onclick="return confirm('确定删除此文章？')" style="color:red">删除</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>