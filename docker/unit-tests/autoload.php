<?php
$_SERVER['HTTP_HOST'] = 'localhost';

require_once('/opt/wordpress/wp-config.php');

echo '=====================================' . PHP_EOL;
echo 'WordPress version: ' . get_bloginfo('version') . PHP_EOL;
echo 'PHP version: ' . phpversion() . PHP_EOL;
echo 'MySQL version: ' . $wpdb->db_version() . PHP_EOL;
echo '=====================================' . PHP_EOL;

echo 'Active plugins:' . PHP_EOL;
$plugins = get_option('active_plugins');
foreach ($plugins as $plugin) {
    echo ' - ' . $plugin . PHP_EOL;
}
echo '=====================================' . PHP_EOL;

spl_autoload_register(function ($class) {
    $plugin_class_dir = '/opt/wordpress/wp-content/plugins/thinkpixel-search-rag/class/';
    if (strncmp($class, 'ThinkPixel\\', 11) === 0) {
        $class = substr($class, 11);
        if (FALSE === ($pos = strrpos($class, '\\'))) return;

        $file = $plugin_class_dir .
            strtolower(substr($class, 0, $pos)) . '/' .
            strtolower(substr($class, $pos + 1)) . '.php';
        if (!file_exists($file)) return;
    } else {
        if (FALSE === ($pos = strpos($class, '\\'))) return;

        $file = $plugin_class_dir .
            'vendor/' .
            strtolower(substr($class, 0, $pos)) . '/' .
            str_replace('\\', '/', substr($class, $pos + 1)) . '.php';
        if (!file_exists($file)) return;
    }

    include($file);
});
