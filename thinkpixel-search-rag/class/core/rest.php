<?php

/**
 * Core of ThinkPixel Plugin
 */

namespace ThinkPixel\Core;

/**
 * Rest Class. The ThinkPixel plugin uses this for REST API operations.
 *
 * @category Plugin
 * @package ThinkPixel
 * @subpackage Core
 * @copyright
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 1.2.0
 */
class Rest
{
    private $db;
    private $settings;
    private $api;

    /**
     * Constructor for the Rest class.
     *
     * @param Db $db Database instance.
     * @param Settings $settings Settings instance.
     * @param Api $api API instance.
     */
    public function __construct(Db $db, Settings $settings, Api $api)
    {
        $this->db = $db;
        $this->settings = $settings;
        $this->api = $api;

        if ($this->settings->has_api_key()) {
            // If API key is set, register the bulk post processing endpoint
            $this->register_bulk_post_processing();
        } else {
            // If API key is not set, register the validation endpoint
            $this->register_validation_endpoint();
            $this->register_key_exchange_request();
        }

        // Pinging and searching own posts is allowed without an API key
        $this->register_ping_endpoint();
        $this->register_skip_search();
        $this->register_debug();
    }

    /**
     * Retrieves the API key from settings.
     *
     * @return string The API key.
     */
    private function get_api_key(): ?string
    {
        return $this->settings->get_api_key();
    }

    /**
     * Stores the API key in settings.
     *
     * @param string $api_key The API key to store.
     * @return void
     */
    private function store_api_key(string $api_key): void
    {
        $this->settings->store_api_key($api_key);
    }

    /**
     * Retrieves the validation token from settings.
     *
     * @return string The validation token.
     */
    private function get_validation_token(): string
    {
        return $this->settings->get_validation_token();
    }

    /**
     * Processes posts using the API.
     *
     * @return void
     */
    private function process_posts(): void
    {
        $this->api->process_posts($this->db);
    }

    /**
     * Gets the count of processed posts.
     *
     * @return int The count of processed posts.
     */
    private function get_processed_post_count(): int
    {
        return $this->db->get_processed_post_count();
    }

    /**
     * Gets the count of unprocessed posts.
     *
     * @return int The count of unprocessed posts.
     */
    private function get_unprocessed_post_count(): int
    {
        return $this->db->get_unprocessed_post_count();
    }

    /**
     * Registers the validation endpoint for the API.
     *
     * @return void
     */
    private function register_validation_endpoint(): void
    {
        if ($this->get_api_key()) return;
        add_action('rest_api_init', function () {
            if ($this->get_api_key()) return;
            // Register the validation endpoint: ?rest_route=/thinkpixel/v1/validate/
            register_rest_route(Strings::RestNamespace, Strings::ValidateRoute, [
                'methods' => 'GET',
                'callback' => [$this, 'handle_validation_request'],
            ]);
        });
    }


    /**
     * Handles the validation request for the API.
     *
     * @return array|\WP_Error The response data or an error.
     */
    public function handle_validation_request(): array|\WP_Error
    {
        $site_url = get_site_url();
        try {
            return [
                'domain' => parse_url($site_url, PHP_URL_HOST),
                'path' => parse_url($site_url, PHP_URL_PATH) ?: '/',
                'validation_token' => $this->get_validation_token(),
                'nonce' => wp_create_nonce(Strings::KeyExchangeNonce),
            ];
        } catch (\Exception $e) {
            return new \WP_Error('no_data', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Registers the key exchange request endpoint for the API.
     *
     * @return void
     */
    private function register_key_exchange_request(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route(Strings::RestNamespace, Strings::ExchangeRoute, [
                'methods' => 'POST',
                'callback' => [$this, 'handle_key_exchange_request'],
            ]);
        });
    }

    /**
     * Handles the key exchange request for the API.
     *
     * @param \WP_REST_Request $request The REST request object.
     * @return \WP_REST_Response The REST response object.
     */
    public function handle_key_exchange_request(\WP_REST_Request $request): \WP_REST_Response
    {
        $data = $request->get_json_params();
        $api_key = $data['api_key'];
        $nonce = $data['nonce'];

        if (!wp_verify_nonce($nonce, Strings::KeyExchangeNonce)) {
            return new \WP_REST_Response([
                'success' => 'false',
                'message' => 'Failed to verify nonce ' . $nonce,
            ], 403);
        }

        $this->store_api_key($api_key);
        return new \WP_REST_Response([
            'success' => 'true',
            'message' => 'API key stored successfully',
        ], 200);
    }

    /**
     * Registers the ping endpoint for the API.
     *
     * @return void
     */
    private function register_ping_endpoint(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route(Strings::RestNamespace, Strings::PingRoute, [
                'methods' => 'POST',
                'callback' => [$this, 'handle_ping_request'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    /**
     * Handles the ping request for the API.
     *
     * @param \WP_REST_Request $request The REST request object.
     * @return \WP_REST_Response The REST response object.
     */
    public function handle_ping_request(\WP_REST_Request $request): \WP_REST_Response
    {
        return $this->api->ping($request);
    }

    /**
     * Registers the bulk post processing endpoint for the API.
     *
     * @return void
     */
    private function register_bulk_post_processing(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route(Strings::RestNamespace, Strings::BulkProcessRoute, [
                'methods' => 'POST',
                'callback' => [$this, 'handle_bulk_post_processing'],
            ]);
        });
    }

    /**
     * Handles the bulk post processing request for the API.
     *
     * @param \WP_REST_Request $request The REST request object.
     * @return \WP_REST_Response The REST response object.
     */
    public function handle_bulk_post_processing(\WP_REST_Request $request): \WP_REST_Response
    {
        $this->process_posts();

        return new \WP_REST_Response([
            'success' => 'true',
            'unprocessed_count' => $this->get_unprocessed_post_count(),
            'processed_count' => $this->get_processed_post_count(),
            'message' => 'Bulk post processing complete',
        ], 200);
    }

    /**
     * Registers the skip search endpoint for the API.
     *
     * @return void
     */
    private function register_skip_search(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route(Strings::RestNamespace, Strings::SkipSearchRoute, [
                'methods' => 'POST',
                'callback' => [$this, 'handle_skip_search'],
            ]);
        });
    }

    /**
     * Handles the skip search request for the API.
     *
     * @param \WP_REST_Request $request The REST request object.
     * @return \WP_REST_Response The REST response object.
     */
    public function handle_skip_search(\WP_REST_Request $request): \WP_REST_Response
    {
        $query = sanitize_text_field($request->get_param('query'));
        $offset = intval($request->get_param('offset') ?? 0);
        $limit = intval($request->get_param('limit') ?? 10);

        if (strlen($query) < 2) {
            $query = '';
        }

        $data = $this->db->get_posts_skip_status_by_keyword($query, $limit, $offset);

        return new \WP_REST_Response([
            'success' => 'true',
            'data' => $data,
        ], 200);
    }

    private function register_debug(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route(Strings::RestNamespace, Strings::DebugRoute, [
                'methods' => 'GET',
                'callback' => [$this, 'handle_debug'],
            ]);
        });
    }

    public function handle_debug(\WP_REST_Request $request): \WP_REST_Response
    {
        $report = [
            'error_occurred' => false,
        ];
        $api_key = $this->settings->get_api_key();
        if ($api_key) {
            $report['get_api_key'] = true;
            $report['get_api_key_details'] = null;
        } else {
            $report['error_occurred'] = true;
            $report['get_api_key'] = false;
            $report['get_api_key_details'] = 'No API key found';
        }

        delete_transient(Strings::JWTTransient);
        $jwt = $this->api->get_jwt();
        $error = $this->api->get_last_error();
        if ($jwt) {
            $report['get_jwt'] = true;
            $report['get_jwt_details'] = [
                'jwt' => $jwt,
                'latency' => $this->api->get_last_latency(),
            ];
        } else {
            $report['error_occurred'] = true;
            $report['get_jwt'] = false;
            $report['get_jwt_details'] = (array) $error;
        }

        $sample_post_id = $this->db->get_sample_post_id();
        if ($sample_post_id) {
            $report['get_sample_post'] = true;
            $report['get_sample_post_details'] = $sample_post_id;
        } else {
            $report['error_occurred'] = true;
            $report['get_sample_post'] = false;
            $report['get_sample_post_details'] = 'No post found';
        }

        $processed = $this->api->process_posts_raw([$sample_post_id]);
        $error = $this->api->get_last_error();
        if (! $error) {
            $report['process_posts_raw'] = true;
            $report['process_posts_raw_details'] = [
                'processed' => $processed,
                'latency' => $this->api->get_last_latency(),
            ];
        } else {
            $report['error_occurred'] = true;
            $report['process_posts_raw'] = false;
            $report['process_posts_raw_details'] = (array) $error;
        }

        $sample_post = get_post($sample_post_id);
        $post_title = apply_filters('the_title', $sample_post->post_title);
        $html_content = $post_title . PHP_EOL . apply_filters('the_content', $sample_post->post_content);
        $text_content = Strings::wp_html_to_plain_text($html_content);
        $report['sample_post_html'] = $html_content;
        $report['sample_post_text'] = $text_content;

        $result = $this->api->do_search($sample_post->post_title);
        $error = $this->api->get_last_error();
        if ($result) {
            $report['do_search'] = true;
            $report['do_search_details'] = [
                'result' => $result,
                'latency' => $this->api->get_last_latency()
            ];
        } else {
            $report['error_occurred'] = true;
            $report['do_search'] = false;
            $report['do_search_details'] = (array) $error;
        }

        if ($report['error_occurred']) {
            return new \WP_REST_Response($report, 500);
        } else {
            return new \WP_REST_Response($report, 200);
        }
    }
}
