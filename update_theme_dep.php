<?php
$files = ['index.php', 'admin.php', 'profile.php'];
foreach ($files as $f) {
    if(file_exists($f)) {
        $content = file_get_contents($f);
        if(strpos($content, 'theme.js') === false) {
            $content = str_replace('</head>', "    <link rel=\"stylesheet\" href=\"assets/css/style.css\">\n    <script src=\"assets/js/theme.js\"></script>\n</head>", $content);
            file_put_contents($f, $content);
            echo "Updated $f\n";
        }
    }
}
