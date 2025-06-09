<?php

/**
 * Core of *
 */

namespace ThinkPixel\Core;

/**
 * UI Class. The ThinkPixel plugin uses this for rendering the Settings page.
 *
 * @category ThinkPixel
 * @package ThinkPixel
 * @subpackage Core
 * @copyright
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 1.1.0
 */
class UI
{
    private $db;
    private $settings;
    private $api;

    private $plugin_basename;
    private $plugin_dir_path;
    private $plugin_dir_url;
    private $templates_path;

    /**
     * Constructor for the UI class.
     *
     * @param string $plugin_file_path The path to the plugin file.
     * @param Db $db Database object.
     * @param Settings $settings Settings object.
     */
    public function __construct(string $plugin_file_path, Db $db, Settings $settings, Api $api)
    {
        $this->db = $db;
        $this->settings = $settings;
        $this->api = $api;

        $this->plugin_dir_path = plugin_dir_path($plugin_file_path);
        $this->plugin_dir_url = plugin_dir_url($plugin_file_path);
        $this->templates_path = $this->plugin_dir_path . 'templates/';
        $this->plugin_basename = plugin_basename($plugin_file_path);

        // Add actions and filters for the admin interface.
        add_action('admin_notices', [$this, 'admin_notices']);
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_filter('plugin_action_links_' . $this->plugin_basename, [$this, 'add_settings_link']);
        add_action('admin_init', [$this, 'api_key_actions_handler']);
        add_action('admin_init', [$this, 'skip_items_actions_handler']);
    }

    /**
     * Retrieves the API key from the settings.
     *
     * @return string|null The API key or null if not set.
     */
    private function get_api_key(): ?string
    {
        return $this->settings->get_api_key();
    }

    /**
     * Retrieves the count of processed posts from the database.
     *
     * @return int The count of processed posts.
     */
    private function get_processed_post_count(): int
    {
        return $this->db->get_processed_post_count();
    }

    /**
     * Retrieves the total count of posts from the database.
     *
     * @return int The total count of posts.
     */
    private function get_total_post_count(): int
    {
        return $this->db->get_total_post_count();
    }

    /**
     * Displays admin notices for the plugin.
     *
     * @return void
     */
    function admin_notices(): void
    {
        // Check if there's an error message in the transient.
        $error_message = get_transient(Strings::ApiErrorTransient);

        if ($error_message) {
            // Display the error message.
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>';
            echo '<strong>' . Strings::PluginName . ' Error: </strong>';
            echo esc_html($error_message);
            echo '</p>';
            echo '</div>';
            // Optionally, delete the transient so the message is shown only once.
            delete_transient(Strings::ApiErrorTransient);
        }
    }

    /**
     * Adds the plugin settings menu to the admin dashboard.
     *
     * @return void
     */
    public function add_settings_page()
    {
        add_options_page(
            __('ThinkPixel Search RAG Settings', Strings::Domain),
            __('ThinkPixel Search RAG', Strings::Domain),
            'manage_options',
            Strings::SettingsSlug,
            [$this, 'render_settings_page']
        );
    }

    /**
     * Renders the settings page for the plugin.
     *
     * @return void
     */
    function render_settings_page()
    {
        // Security check
        if (! current_user_can('manage_options')) {
            return;
        }

        // Save or regenerate API Key logic can be handled via POST or AJAX (example below).

        // Enqueue scripts for real-time /ping checks (AJAX polling every 20 seconds).
        wp_enqueue_script(
            Strings::SettingsJS,
            $this->plugin_dir_url . 'assets/js/admin.js',
            ['jquery'],
            Strings::PluginVersion,
            true
        );

        // Enqueue styles for the settings page.
        wp_enqueue_style(
            Strings::SettingsCSS,
            $this->plugin_dir_url . 'assets/css/admin.css',
            [],
            Strings::PluginVersion
        );

        // Localize script to pass any necessary data or nonce
        wp_localize_script(
            Strings::SettingsJS,
            Strings::SettingsJSObject,
            [
                'wp_rest_nonce' => wp_create_nonce('wp_rest'),
                'ping_url' => rest_url(Strings::RestNamespace . '/' . Strings::PingRoute),
                'skip_search_url' => rest_url(Strings::RestNamespace . '/' . Strings::SkipSearchRoute),
                'bulk_post_processing_url' => rest_url(Strings::RestNamespace . '/' . Strings::BulkProcessRoute),
                'text' => [
                    'now' => __('Now', Strings::Domain),
                    'minutesAgo' => __('minutes ago', Strings::Domain),
                    'hourAgo' => __('1 hour ago', Strings::Domain),
                    'today' => __('Today', Strings::Domain),
                    'ok' => __('OK', Strings::Domain),
                    'error' => __('Error', Strings::Domain),
                    'minChars' => __('Please enter at least 2 characters.', Strings::Domain),
                    'bulkSuccess' => __('Bulk processing completed successfully.', Strings::Domain),
                    'bulkError' => __('An error occurred during bulk indexing: ', Strings::Domain),
                    'bulkRequestError' => __('Error calling bulk processing API.', Strings::Domain),
                    'bulkResposeError' => __('Bulk processing API returned an error.', Strings::Domain),
                ]
            ]
        );

        // Include the settings page template.
        include($this->templates_path . 'settings-page.php');
    }

    /**
     * Adds a settings link to the plugin action links.
     *
     * @param array $links The existing plugin action links.
     * @return array The modified plugin action links.
     */
    public function add_settings_link($links)
    {
        $settings_link = '<a href="options-general.php?page=' . Strings::SettingsSlug . '">'
            . __('Settings', Strings::Domain)
            . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Renders the API key section of the settings page.
     *
     * @return void
     */
    function render_api_key_section()
    {
        $api_key = $this->get_api_key();

        // Include the API key section template.
        include($this->templates_path . 'api-key-section.php');
    }

    /**
     * Renders the API health section of the settings page.
     *
     * @return void
     */
    function render_api_health_section()
    {
        // Include the API health section template.
        include($this->templates_path . 'api-health-section.php');
    }

    /**
     * Renders the page indexing section of the settings page.
     *
     * @return void
     */
    function render_page_indexing_section()
    {
        $processed_count = $this->get_processed_post_count();
        $total_count = $this->get_total_post_count();
        $remaining_count = $total_count - $processed_count;

        // Include the page indexing section template.
        include($this->templates_path . 'page-indexing-section.php');
    }

    /**
     * Renders the skip items section of the settings page.
     *
     * @return void
     */
    function render_skip_items_section()
    {
        // Include the skip pages section template.
        include($this->templates_path . 'skip-items-section.php');
    }

    /**
     * Handles actions related to the API key.
     *
     * @return void
     */
    public function api_key_actions_handler()
    {
        if (
            isset($_POST[Strings::ApiKeyNonce])
            && wp_verify_nonce($_POST[Strings::ApiKeyNonce], Strings::ApiKeyAction)
            && current_user_can('manage_options')
        ) {
            if (isset($_POST['thinkpixel_generate_api_key']) || isset($_POST['thinkpixel_request_new_api_key'])) {
                // Call your remote API to register plugin and get new API key
                $new_key = $this->get_api_key_from_remote();
                if (is_wp_error($new_key) || empty($new_key)) {
                    set_transient(Strings::ApiErrorTransient, __('Error generating API key.', Strings::Domain), 30);
                } else {
                    update_option(Strings::ApiKeyOption, sanitize_text_field($new_key));
                }
            } elseif (isset($_POST['thinkpixel_regenerate_api_key'])) {
                // Call your remote API to regenerate key
                $refreshed_key = $this->refresh_api_key_remote();
                if (is_wp_error($refreshed_key) || empty($refreshed_key)) {
                    set_transient(Strings::ApiErrorTransient, __('Error regenerating API key.', Strings::Domain), 30);
                } else {
                    $this->settings->store_api_key($refreshed_key);
                }
            }
        }
    }

    public function skip_items_actions_handler()
    {
        if (
            isset($_POST[Strings::SkipItemsNonce])
            && wp_verify_nonce($_POST[Strings::SkipItemsNonce], Strings::SkipItemsAction)
            && current_user_can('manage_options')
        ) {
            if (isset($_POST['thinkpixel_skip_selected_items'])) {
                // Handle skip/unskip selected pages
                $skip_ids = isset($_POST['skip_ids']) ? json_decode(stripslashes(($_POST['skip_ids']))) : [];
                if (is_array($skip_ids) && ! empty($skip_ids)) {
                    $skip_ids = $this->api->remove_posts_from_index($skip_ids);
                    if (empty($skip_ids)) {
                        set_transient(Strings::ApiErrorTransient, __('Error skipping pages.', Strings::Domain), 30);
                    }
                    $this->db->update_skip_status_for_posts($skip_ids, 1);
                } else {
                    set_transient(Strings::ApiErrorTransient, __('No pages selected.', Strings::Domain), 30);
                }
            }
        }
    }

    /**
     * Retrieves a new API key from the remote server.
     *
     * @return string The new API key.
     */
    function get_api_key_from_remote(): string
    {
        return '';
    }

    /**
     * Regenerates the API key from the remote server.
     *
     * @return string The regenerated API key.
     */
    function refresh_api_key_remote(): ?string
    {
        return $this->api->refresh_api_key();
    }
}
