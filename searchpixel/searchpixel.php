<?php
/*
Plugin Name: Search Pixel
Plugin URI: https://searchpixel.io/
Description: Search Pixel enhances WordPress search by using machine learning generated text embeddings from your website posts and pages to help provide meaningful results to your users' queries. As the vast majority of wordpress hosting services do not provide the necessary resources to run machine learning models, Search Pixel uses the SearchPixel API to generate the embeddings and provide the search results. The plugin connects to the SearchPixel API using a secure connection and does not store any user data on the SearchPixel servers, with the exception of the text embeddings.
Author: Bogdan Dobrica
Version: 1.4.0
Author URI: https://ublo.ro/
Text Domain: searchpixel
Domain Path: /languages
License: Apache-2.0
*/

defined('ABSPATH') or die('No script kiddies please!');

spl_autoload_register(function ($class) {
    if (strncmp($class, 'SearchPixel\\', 12) === 0) {
        $class = substr($class, 12);
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

if (file_exists(__DIR__ . '/searchpixel-config.php'))
    include(__DIR__ . '/searchpixel-config.php');

$tp_plugin = new \SearchPixel\Core\Plugin(__FILE__);
