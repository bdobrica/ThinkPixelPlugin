<?php

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$prefix = $wpdb->prefix . 'searchpixel_';

$wpdb->query('DROP TABLE IF EXISTS `' . $prefix . 'page_log`');
$wpdb->query('DROP TABLE IF EXISTS `' . $prefix . 'search_cache`');

delete_option('searchpixel_api_key');
delete_transient('searchpixel_jwt');
delete_transient('searchpixel_validation_token');
delete_transient('searchpixel_max_batch_text_size');
