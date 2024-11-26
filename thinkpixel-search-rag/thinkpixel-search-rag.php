<?php
/*
Plugin Name: ThinkPixel Search and RAG Plugin
Plugin URI: https://thinkpixel.io/
Description: ThinkPixel Search and RAG Plugin
Author: Bogdan Dobrica
Version: 0.1.0
Author URI: https://ublo.ro/
*/

defined('ABSPATH') or die('No script kiddies please!');

spl_autoload_register(function ($class) {
    if (strncmp($class, 'ThinkPixel\\', 11) === 0) {
        $class = substr($class, 11);
        if (FALSE === ($pos = strrpos($class, '\\'))) return;

        $file = __DIR__ . '/class/' . strtolower(substr($class, 0, $pos)) . '/' . strtolower(substr($class, $pos + 1)) . '.php';
        if (!file_exists($file)) return;
    } else {
        if (FALSE === ($pos = strpos($class, '\\'))) return;

        $file = __DIR__ . '/class/vendor/' . strtolower(substr($class, 0, $pos)) . '/' . str_replace('\\', '/', substr($class, $pos + 1)) . '.php';
        if (!file_exists($file)) return;
    }

    include($file);
});

if (file_exists('think-pixel-config.php'))
    include('think-pixel-config.php');

$tp_plugin = new \ThinkPixel\Core\Plugin(__FILE__);
