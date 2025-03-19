<?php
require_once 'auth.php';
check_login();

// ================== 路径处理 ==================
$rel_path = isset($_GET['path']) ? trim($_GET['path'], '/') : '';
$filename = pathinfo($rel_path, PATHINFO_FILENAME); // 去除扩展名

// 路径安全验证
$md_root = realpath('../md');
$valid_path = false;

$possible_paths = [
    "../md/{$filename}.md.bak",
    "../md/{$filename}.md",
    "../md/{$rel_path}.bak",
    "../md/{$rel_path}"
];

foreach ($possible_paths as $path) {
    if (file_exists($path) && strpos(realpath($path), $md_root) === 0) {
        $current_path = $path;
        $valid_path = true;
        break;
    }
}

if (!$valid_path) {
    die("非法文件路径或文件不存在");
}

// ================== 分类处理 ==================
function get_categories() {
    $categories = ['未分类'];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('../md', FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isDir()) {
            $rel_path = str_replace(realpath('../md').'/', '', $file->getRealPath());
            if ($rel_path) $categories[] = $rel_path;
        }
    }
    return array_unique($categories);
}

// 处理保存请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_title = pathinfo($_POST['title'], PATHINFO_FILENAME); // 清理标题
    $new_category = trim($_POST['category'], '/');
    $content = $_POST['content'];
    
    // 生成新路径
    $new_dir = "../md/{$new_category}";
    $new_publish_path = "{$new_dir}/{$new_title}.md";
    $new_draft_path = "{$new_publish_path}.bak";
    
    // 创建目录
    if (!file_exists($new_dir)) {
        mkdir($new_dir, 0755, true);
    }
    
    // 保存逻辑
    $need_save = true;
    if (file_exists($new_publish_path)) {
        $original = file_get_contents($new_publish_path);
        $need_save = ($content!== $original);
    }
    
    if ($need_save) {
        // 保存草稿或发布
        if (isset($_POST['publish']) && $_POST['publish'] === '1') {
            file_put_contents($new_publish_path, $content);
            if (file_exists($new_draft_path)) {
                unlink($new_draft_path);
            }
            exec('php ../json.php');
            header("Location: posts.php");
            exit;
        } else {
            file_put_contents($new_draft_path, $content);
        }
    }
    
    header("Location: draft.php");
    exit;
}

// ================== 读取内容 ==================
$content = '';
if (pathinfo($current_path, PATHINFO_EXTENSION) === 'bak') {
    $content = file_get_contents($current_path);
} elseif (file_exists($current_path)) {
    $content = file_get_contents($current_path);
}

// ================== 当前信息 ==================
$current_category = str_replace($md_root.'/', '', dirname(realpath($current_path)));
$current_title = pathinfo($current_path, PATHINFO_FILENAME);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($current_title) ?> - 编辑</title>
    <link rel="stylesheet" href="../github-markdown.css">
    <link rel="stylesheet" href="../github.min.css">
    <style>
       .editor-container { display: grid; grid-template-columns: 280px 1fr; gap: 20px; }
       .sidebar { border-right: 1px solid #ddd; padding-right: 20px; }
       .path-info { color: #666; margin: 10px 0; }
        #preview { padding: 20px; border: 1px solid #eee; margin-top: 20px; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="editor-container" style="padding:20px;">
        <!-- 左侧控制面板 -->
        <div class="sidebar">
            <h2>文章设置</h2>
            <div class="path-info">
                当前路径：<?= htmlspecialchars(dirname($rel_path)) ?><br>
                保存位置：<span id="savePath"></span>
            </div>
            
            <form id="saveForm">
                <div style="margin-bottom:15px;">
                    <label>文章标题：
                        <input type="text" name="title" value="<?= htmlspecialchars($current_title) ?>" 
                               required style="width:100%">
                    </label>
                </div>
                
                <div style="margin-bottom:15px;">
                    <label>分类目录：
                        <select name="category" id="categorySelect" style="width:100%">
                            <?php foreach(get_categories() as $cat): ?>
                                <option value="<?= $cat ?>" <?= $cat === $current_category? 'selected' : ''?>>
                                    <?= $cat ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                
                <div style="margin-bottom:15px;">
                    <label>新建分类：
                        <input type="text" id="newCategory" placeholder="例如: 技术/编程" style="width:100%">
                    </label>
                    <button type="button" onclick="addCategory()" style="margin-top:5px;">添加分类</button>
                </div>
                
                <div style="margin-top:20px;">
                    <button type="button" id="saveDraftButton" class="button">保存草稿</button>
                    <button type="button" id="publishButton" class="button primary">直接发布</button>
                </div>
            </form>
        </div>

        <!-- 右侧编辑器 -->
        <div>
            <textarea id="editor" name="content" 
                     style="width:100%;height:70vh;font-family: monospace;"><?= htmlspecialchars($content) ?></textarea>
            <h3>实时预览</h3>
            <div id="preview" class="markdown-body"></div>
        </div>
    </div>

    <!-- 固定资源路径 -->
    <script src="../libs/marked/marked.min.js"></script>
    <script src="../libs/dompurify/purify.min.js"></script>
    <script src="../libs/highlight.js/highlight.min.js"></script>
    <script src="../libs/mathjax/es5/tex-mml-chtml.js"></script>
    
    <script>
    // ================== 动态路径显示 ==================
    function updatePath() {
        const title = document.querySelector('[name="title"]').value.replace(/\.md$/, '');
        const category = document.querySelector('[name="category"]').value;
        document.getElementById('savePath').textContent = `${category}/${title}.md`;
    }

    // ================== 分类管理 ==================
    function addCategory() {
        const newCat = document.getElementById('newCategory').value.trim();
        if (!newCat) return;
        
        const select = document.getElementById('categorySelect');
        if (!Array.from(select.options).some(opt => opt.value === newCat)) {
            const option = new Option(newCat, newCat);
            select.add(option);
        }
        select.value = newCat;
        updatePath();
    }

    // ================== 预览渲染 ==================
    function renderPreview() {
        const markdown = document.getElementById('editor').value;
        const html = marked.parse(markdown, {
            breaks: true,
            highlight: code => hljs.highlightAuto(code).value
        });
        document.getElementById('preview').innerHTML = DOMPurify.sanitize(html);
        MathJax.typeset();
    }

    // ================== 保存逻辑 ==================
    document.getElementById('saveDraftButton').addEventListener('click', function() {
        saveContent(false);
    });

    document.getElementById('publishButton').addEventListener('click', function() {
        saveContent(true);
    });

    function saveContent(publish) {
        const form = document.getElementById('saveForm');
        const formData = new FormData(form);
        formData.append('content', document.getElementById('editor').value);
        formData.append('publish', publish? '1' : '0');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>', true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                if (publish) {
                    window.location.href = 'posts.php';
                } else {
                    window.location.href = 'draft.php';
                }
            }
        };
        xhr.send(formData);
    }

    // ================== 事件监听 ==================
    document.querySelectorAll('[name="title"], [name="category"]').forEach(el => {
        el.addEventListener('input', updatePath);
    });
    
    document.getElementById('editor').addEventListener('input', () => {
        requestAnimationFrame(renderPreview);
    });

    // 初始化
    updatePath();
    renderPreview();
    </script>
</body>
</html>
