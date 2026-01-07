<?php
// INSTALLER SCRIPT - COMPATIBILITY MODE
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Start</title>
    <style>body{font-family:sans-serif;background:#111;color:#eee;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;} .box{background:#222;padding:2rem;border-radius:10px;text-align:center;} .ok{color:#4ade80;} .fail{color:#ef4444;}</style>
</head>
<body>
<div class="box">
    <h2>System Status</h2>
    <p>PHP Version: <?php echo phpversion(); ?></p>
    <?php
    $file = dirname(__FILE__) . '/members_data.json';
    
    // Create
    if (!file_exists($file)) {
        $init = '{"families":[],"members":[]}';
        if (@file_put_contents($file, $init) !== false) echo "<div class='ok'>✅ Created Data File</div>";
        else echo "<div class='fail'>❌ Cannot Create File (Permission Denied)</div>";
    }

    // Permission
    @chmod($file, 0777);

    // Test
    if (is_writable($file)) {
        echo "<h3 class='ok'>✅ SYSTEM READY</h3>";
        echo "<a href='admin.html' style='color:#fff;background:green;padding:10px 20px;text-decoration:none;border-radius:5px;'>Enter Admin Panel</a>";
    } else {
        echo "<h3 class='fail'>❌ WRITE PROTECTED</h3>";
        echo "<p>Go to CloudPanel > File Manager. Set permissions of <b>members_data.json</b> to <b>777</b>.</p>";
        echo "<button onclick='location.reload()'>Check Again</button>";
    }
    ?>
</div>
</body>
</html>
