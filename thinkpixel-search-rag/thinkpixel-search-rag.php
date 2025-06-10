<?php
/*
Plugin Name: ThinkPixel Search and RAG
Plugin URI: https://thinkpixel.io/
Description: ThinkPixel Search and RAG enhances WordPress search by using machine learning generated text embeddings from the your website posts and pages to help provide meaningful results to your users' queries. As the vast majority of wordpress hosting services do not provide the necessary resources to run machine learning models, ThinkPixel Search and RAG uses the ThinkPixel API to generate the embeddings and provide the search results. The plugin connects to the ThinkPixel API using a secure connection and does not store any user data on the ThinkPixel servers, with the exception of the text embeddings.
Author: Bogdan Dobrica
Version: 1.1.2
Author URI: https://ublo.ro/
Text Domain: thinkpixel-search-rag
Domain Path: /languages
License: Apache-2.0
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
