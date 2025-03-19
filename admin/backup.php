<?php
// admin/backup.php
require_once 'auth.php';
check_login();

if (isset($_GET['create'])) {
    $zip = new ZipArchive();
    $backup_name = 'backup_'.date('Ymd_His').'.zip';
    
    if ($zip->open('../'.$backup_name, ZipArchive::CREATE) === TRUE) {
        addFolderToZip('../md', $zip);
        $zip->close();
        echo "备份成功：<a href='../$backup_name'>$backup_name</a>";
    } else {
        echo "备份失败";
    }
    exit;
}

function addFolderToZip($folder, &$zip, $parent = null) {
    $files = scandir($folder);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        $path = "$folder/$file";
        $local = $parent ? "$parent/$file" : $file;
        if (is_dir($path)) {
            $zip->addEmptyDir($local);
            addFolderToZip($path, $zip, $local);
        } else {
            $zip->addFile($path, $local);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>数据备份</title>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div style="padding:20px;">
        <h2>数据备份</h2>
        <button onclick="location.href='backup.php?create=1'">立即备份MD文件夹</button>
        <p>备份文件将保存在网站根目录下，文件名格式：backup_年月日_时分秒.zip</p>
    </div>
</body>
</html>