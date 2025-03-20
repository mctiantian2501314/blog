<?php // admin/nav.php ?>
    <style>
        nav { background: #f0f0f0; padding: 10px; margin-bottom: 20px; }
        nav a { margin-right: 15px; text-decoration: none; }
        .container { display: flex; gap: 20px; }
        .sidebar { width: 200px; }
        .main-content { flex: 1; }
    </style>
    <script src="../libs/jquery/jquery-3.7.1.js"></script>
    <script src="../json.php"></script>
    <style>
        /* 弹窗样式 */
        #popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z - index: 1;
        }

        #popup-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border: 1px solid #ccc;
            border - radius: 5px;
        }
    </style>
</head>
<nav>
    <a href="/admin">首页</a>
    <a href="posts.php">文章</a>
    <a href="config.php">配置</a>
    <a href="backup.php">备份</a>
    <a href="sitemap.php">站点地图</a>
    <a href="?logout">退出</a>
    <a href="/">网站首页</a>
        <button id="fetchButton">更新内容</button>
</nav>


    <!-- 弹窗 -->
    <div id="popup">
        <div id="popup-content">
            <span id="closePopup" style="cursor: pointer; float: right;">X</span>
            <pre id="popupText"></pre>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#fetchButton').click(function () {
                $.ajax({
                    type: 'GET',
                    url: '../json.php',
                    success: function (response) {
                        // 在弹窗内容后添加指定提示信息
                        var newContent = response + '\n文章列表内容已更新';
                        $('#popupText').text(newContent);
                        $('#popup').show();
                    },
                    error: function () {
                        alert('获取内容失败');
                    }
                });
            });

            $('#closePopup').click(function () {
                $('#popup').hide();
            });
        });
    </script>


