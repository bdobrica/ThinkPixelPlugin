<?php

/**
 * Core of *
 */

namespace ThinkPixel\Core;

/**
 * Plugin Class. The ThinkPixel plugin is an instance of this class.
 *
 * @category ThinkPixel
 * @package ThinkPixel
 * @subpackage Core
 * @copyright
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 0.1.1
 */
class Plugin
{
    private $db;
    private $api;
    private $settings;
    private $cron;
    private $rest;
    private $ui;
    private $plugin_file_path;

    /**
     * Constructor for the Plugin class.
     *
     * @param string $plugin_file_path Path to the plugin file.
     */
    public function __construct(string $plugin_file_path)
    {
        $this->plugin_file_path = $plugin_file_path;

        $this->db = new Db();
        $this->settings = new Settings();
        $this->api = new Api([$this->settings, 'get_api_key']);
        $this->cron = new Cron([$this, 'scheduled_post_processing']);
        $this->rest = new Rest($this->db, $this->settings, $this->api);
        $this->ui = new UI($plugin_file_path, $this->db, $this->settings);

        $this->register_activation_hook();
        $this->register_deactivation_hook();
        $this->register_post_tracking();

        if ($this->settings->has_api_key()) {
            $this->register_custom_search();
        }
    }

    /**
     * Registers the site with the ThinkPixel API.
     *
     * @return void
     */
    private function register_site(): void
    {
        if ($this->settings->has_api_key()) return;
        try {
            $site_stats = $this->db->calculate_site_stats();
            $response = $this->api->register_site($site_stats);
            $validation_token = $response['validation_token'];
            $validation_token_expires_at = $response['validation_token_expires_at'];
            $this->settings->store_validation_token($validation_token, $validation_token_expires_at);
        } catch (\Exception $e) {
            error_log(Strings::PluginName . ' registration error: ' . $e->getMessage());
        }
    }

    /**
     * Registers the activation hook.
     *
     * @return void
     */
    private function register_activation_hook(): void
    {
        register_activation_hook($this->plugin_file_path, function () {
            $this->db->create_tables();
            $this->db->sync_posts_to_log();
            $this->cron->schedule_cron_job();
            $this->register_site();
        });
    }

    /**
     * Registers the deactivation hook.
     *
     * @return void
     */
    private function register_deactivation_hook(): void
    {
        register_deactivation_hook($this->plugin_file_path, function () {
            $this->db->drop_tables();
            $this->cron->unschedule_cron_job();
            $this->api->cleanup();
            $this->settings->cleanup();
        });
    }

    /**
     * Registers hooks for tracking post changes.
     *
     * @return void
     */
    private function register_post_tracking(): void
    {
        add_action('save_post', [$this->db, 'track_post']);
        add_action('trash_post', [$this->db, 'trash_post']);
    }

    /**
     * Performs a custom search using the ThinkPixel API.
     *
     * @param \WP_Query $query The WordPress query object.
     * @return void
     */
    public function do_custom_search(\WP_Query $query): void
    {
        // Ensure this only affects the main query on the search page.
        if (!is_admin() && $query->is_main_query() && $query->is_search()) {
            // Get the search term.
            $search_query = get_search_query();
            $query->set('s', ''); // Clear the search query.

            // Call the ThinkPixel API.
            $result = $this->db->get_cached_search_results($search_query);
            if (is_null($result)) {
                $result = $this->api->do_search($search_query);
                $this->db->cache_search_results($search_query, $result);
            }

            // Extract post IDs from the response.
            $data = $result['results'] ?? [];
            $post_ids = array_column($data, 'id');

            if (!empty($post_ids)) {
                // Replace the query results with the fetched post IDs.
                $query->set('post__in', $post_ids);
                $query->set('orderby', 'post__in'); // Maintain the order returned by the API.
            } else {
                // If no results, force an empty result set.
                $query->set('post__in', [0]);
            }
        }
    }

    /**
     * Registers the custom search functionality.
     *
     * @return void
     */
    public function register_custom_search(): void
    {
        add_action('pre_get_posts', [$this, 'do_custom_search']);
    }
}
