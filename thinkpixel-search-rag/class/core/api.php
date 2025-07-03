<?php

/**
 * Core of *
 */

namespace ThinkPixel\Core;

/**
 * Api Class. The ThinkPixel plugin uses this class for interacting with the ThinkPixel API Gateway.
 * It provides methods for:
 * - Registering the site with the API.
 * - Processing posts and sending them to the API.
 * - Performing searches using the API.
 * - Fetching a JWT token from the API.
 * - Pinging the API to check its availability.
 *
 * @category ThinkPixel
 * @package ThinkPixel
 * @subpackage Core
 * @copyright
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 1.3.0
 */
class Api
{
    private $get_api_key_callback;
    private $last_error;
    private $last_latency;
    private $post_timeout;
    private $ping_timeout;

    /**
     * Constructor for the Api class.
     *
     * @param callable $get_api_key_callback Callback function to get the API key.
     */
    public function __construct(callable $get_api_key_callback)
    {
        $this->get_api_key_callback = $get_api_key_callback;
        $this->last_error = null;
        $this->last_latency = null;
        // Set request timeouts based on server settings.
        // The post_timeout is set to the minimum of 30 seconds, 90% of the max execution time, and the default socket timeout.
        // This ensures that the API requests do not exceed the server's execution limits.
        // Filter out values less than or equal to 0 (which means infinite timeout)
        $this->post_timeout = min(array_filter([
            30,
            (int) (0.9 * (int)ini_get('max_execution_time') ?: 30),
            (int) (ini_get('default_socket_timeout') ?: 30),
        ], function ($v) {
            return $v > 0;
        }));
        $this->ping_timeout = 1;
        // add_action('wp_ajax_' . Strings::Plugin, [$this, 'api']);
    }


    /**
     * Registers the site with the ThinkPixel API.
     *
     * @param array $site_stats An array of site statistics.
     * @return array The response data from the API.
     * @throws \Exception If there is an error communicating with the API.
     */
    public function register_site(array $site_stats): array
    {
        // Get the site URL.
        $site_url = get_site_url();
        // Merge site statistics with domain and path information.
        $site_data = array_merge(
            $site_stats,
            [
                'domain' => parse_url($site_url, PHP_URL_HOST),
                'path' => parse_url($site_url, PHP_URL_PATH) ?: '/',
            ]
        );

        // Measure the start time for latency calculation.
        $start_time = microtime(true);
        // Send a POST request to the API to register the site.
        $response = wp_remote_post(Strings::RegisterEndpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($site_data),
            'timeout' => $this->post_timeout, // Set the timeout for the request.
        ]);
        // Calculate the latency.
        $this->last_latency = microtime(true) - $start_time;

        // Check if there was an error in the response.
        if (is_wp_error($response)) {
            throw new \Exception('Error communicating with API Gateway: ' . $response->get_error_message());
        }

        // Decode the response data.
        $response_data = json_decode(wp_remote_retrieve_body($response), true);
        // Validate the response data.
        if (empty($response_data['validation_token']) || empty($response_data['validation_token_expires_at'])) {
            throw new \Exception('Invalid response from API Gateway.');
        }

        return $response_data;
    }

    /**
     * Gets the maximum batch text size for a batch of posts.
     * This method is used to determine the maximum size of text that can be sent in a single batch to the ThinkPixel API.
     * 
     * @return int The maximum batch text size in bytes. Defaults to 1024 bytes if not set in the response.
     */
    private function fetch_max_batch_text_size(): int
    {
        $start_time = microtime(true);
        $response = wp_remote_post(Strings::MaxBatchTextSizeEndpoint, [
            'headers' => [
                "Authorization" => 'Bearer ' . $this->get_jwt(),
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'timeout' => $this->post_timeout, // Allow the API to set a custom timeout based on client timeout.
            ]),
            'timeout' => $this->post_timeout, // Set the timeout for the request.
        ]);
        $this->last_latency = microtime(true) - $start_time;

        $max_batch_text_size = 1024; // Default value if not set in the response.
        $max_batch_text_size_expires_at = 2 * HOUR_IN_SECONDS; // Default expiration time.

        // Check if the response is an error.
        if (is_wp_error($response)) {
            error_log('Error fetching max batch text size: ' . $response->get_error_message());
        } else {
            // Decode the response body.
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            // Log the response for debugging.
            error_log('Max batch text size response: ' . var_export($data, true));
            // Check if the data is valid and contains the max batch text size.
            if (isset($data['max_batch_text_size']) && is_int($data['max_batch_text_size'])) {
                $max_batch_text_size = (int) $data['max_batch_text_size'];
                $max_batch_text_size_expires_at = $data['exp'] ? (int) $data['exp'] - time() : 12 * HOUR_IN_SECONDS;
                // Store the max batch text size in a transient for caching.
            }
            // If the response is invalid, log an error and return a default value.
            error_log('Invalid response fetching max batch text size: ' . $body);
        }

        set_transient(Strings::MaxBatchTextSizeTransient, $max_batch_text_size, $max_batch_text_size_expires_at);
        return $max_batch_text_size;
    }

    /**
     * Gets the maximum batch text size, either from the transient or by fetching it from the API.
     * 
     * @return int The maximum batch text size in bytes.
     */
    private function get_max_batch_text_size(): int
    {
        // Try to get the max batch text size from the transient.
        $max_batch_text_size = get_transient(Strings::MaxBatchTextSizeTransient);
        if ($max_batch_text_size !== false) {
            return $max_batch_text_size;
        }

        // If the transient is not set, fetch it from the API.
        $max_batch_text_size = $this->fetch_max_batch_text_size();
        return $max_batch_text_size;
    }

    /**
     * Processes raw posts data and sends it to the ThinkPixel API.
     * Note: This method processes posts in batches, ensuring that the total size of the batch does not exceed the maximum allowed size. So not all posts may be processed in one go.
     *
     * @param array $unprocessed_ids Array of unprocessed post IDs.
     * @return array Array of processed post IDs.
     */
    public function process_posts_raw(array $unprocessed_ids): array
    {
        $pages_data = [];

        $max_batch_text_size = $this->get_max_batch_text_size(); // Get the maximum batch text size.
        $current_batch_size = 0; // Initialize the current batch size.

        // Loop through each unprocessed post ID.
        foreach ($unprocessed_ids as $unprocessed_id) {
            $post = get_post($unprocessed_id); // Get the post object.
            $title = apply_filters('the_title', $post->post_title); // Apply filters to the post title.
            $html_content = apply_filters('the_content', $post->post_content); // Apply filters to the post content.
            $html_content = $title . PHP_EOL . $html_content; // Combine title and content.
            $md_content = HTML2MD::convert($html_content); // Convert HTML content to Markdown.
            $md_content_size = strlen($md_content); // Get the size of the Markdown content.

            if ($current_batch_size > 0 && ($current_batch_size + $md_content_size) > $max_batch_text_size) {
                // If adding this post exceeds the max batch size, send the current batch.
                break;
            }
            $current_batch_size += $md_content_size; // Update the current batch size.

            // Prepare the data for each post.
            $pages_data[] = [
                'id' => $post->ID,
                'text' => $md_content,
                'extra' => [
                    'title' => $post->post_title,
                    'type' => $post->post_type,
                ]
            ];
        }

        // Post data to ThinkPixel API.
        $start_time = microtime(true); // Record the start time.
        $response = wp_remote_post(Strings::StoreEndpoint, [
            'body' => json_encode($pages_data), // Encode the data to JSON.
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->get_jwt(), // Add authorization header.
            ],
            'timeout' => $this->post_timeout, // Set the timeout.
        ]);
        $this->last_latency = microtime(true) - $start_time; // Calculate the latency.

        // Log the API response.
        error_log(Strings::StoreEndpoint . ' response ' . var_export($response, true));

        // Decode the response body.
        if (isset($response->stored_ids) && is_array($response->stored_ids) && count($response->stored_ids) > 0) {
            return $response->stored_ids; // Return the stored IDs if available.
        }

        // Check for errors in the response.
        if (isset($response->error)) {
            error_log(Strings::PluginName . ' API error: ' . $response->error);
            return [];
        }

        // Return the processed post IDs.
        return array_map(fn($data) => $data['id'], $pages_data);
    }

    /**
     * Processes posts in batches.
     *
     * @param Db $db Database object.
     * @param int $batch_size Number of posts to process in each batch.
     * @return array Array of processed post IDs.
     */
    public function process_posts(Db $db, int $batch_size = 10): array
    {
        // Get unprocessed post IDs from the database.
        $unprocessed_ids = $db->get_unprocessed_posts($batch_size);
        error_log('Unprocessed IDs: ' . var_export($unprocessed_ids, true));

        // If there are no unprocessed IDs, return an empty array.
        if (!$unprocessed_ids) return [];

        // Process the unprocessed posts.
        $processed_ids = $this->process_posts_raw($unprocessed_ids);
        error_log('Processed IDs: ' . var_export($processed_ids, true));
        if ($processed_ids) {
            $db->mark_processed_posts($processed_ids);
        }

        return $processed_ids;
    }

    /**
     * Performs a search using the ThinkPixel API.
     *
     * @param string $search_query The search query.
     * @return array The search results.
     */
    public function do_search(string $search_query): array
    {
        // Call ThinkPixel API using POST.
        $start_time = microtime(true);
        $response = wp_remote_post(Strings::SearchEndpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_jwt(),
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'text' => $search_query,
            ]),
            'timeout' => $this->post_timeout, // Set the timeout for the request.
        ]);
        $this->last_latency = microtime(true) - $start_time;

        // Handle errors in the response.
        if (is_wp_error($response)) {
            error_log('Error calling ' . Strings::PluginName . ' API: ' . $response->get_error_message());
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data) || !is_array($data)) {
            error_log('Invalid response from ' . Strings::PluginName . ' API.');
            return [];
        }

        return $data;
    }

    /**
     * Refreshes the API key by calling the ThinkPixel API.
     * 
     * @return string|null The new API key or null if there was an error.
     */
    public function refresh_api_key(): ?string
    {
        $start_time = microtime(true);
        $response = wp_remote_post(Strings::RefreshApiKeyEndpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_jwt(),
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([]),
            'timeout' => $this->post_timeout, // Set the timeout for the request.
        ]);
        $this->last_latency = microtime(true) - $start_time;

        // Handle errors in the response.
        if (is_wp_error($response)) {
            error_log('Error calling ' . Strings::PluginName . ' API: ' . $response->get_error_message());
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['api_key'])) {
            error_log('Invalid response from ' . Strings::PluginName . ' API.');
            return null;
        }

        return $data['api_key'];
    }

    /**
     * Fetches a JWT token from the ThinkPixel API.
     *
     * @param string $api_key The API key.
     * @return string|null The JWT token or null if there was an error.
     */
    public function fetch_jwt(string $api_key): ?string
    {
        if (!$api_key) {
            $this->last_error = new Error(__CLASS__, __FUNCTION__, 'API Key not set');
            return null;
        }

        $start_time = microtime(true);
        $response = wp_remote_post(Strings::AuthTokenEndpoint, [
            'headers' => [
                'X-API-Key' => $api_key,
            ],
            'timeout' => $this->post_timeout, // Set the timeout for the request.
        ]);
        $this->last_latency = microtime(true) - $start_time;

        if (is_wp_error($response)) {
            $this->last_error = new Error(__CLASS__, __FUNCTION__, 'Error fetching JWT: ' . $response->get_error_message());
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['token']) && isset($data['exp'])) {
            // Store JWT in a transient for automatic expiration handling.
            set_transient(Strings::JWTTransient, $data['token'], $data['exp'] - time());
            $this->last_error = null;
            return $data['token'];
        }

        $this->last_error = new Error(__CLASS__, __FUNCTION__, 'Invalid response fetching ' . Strings::PluginName . ' JWT: ' . $body);
        return null;
    }

    /**
     * Gets a JWT token, fetching a new one if necessary.
     *
     * @return string|null The JWT token or null if there was an error.
     */
    public function get_jwt(): ?string
    {
        $jwt = get_transient(Strings::JWTTransient);
        if ($jwt) return $jwt;

        $api_key = call_user_func($this->get_api_key_callback);
        return $this->fetch_jwt($api_key);
    }

    /**
     * Pings the ThinkPixel API to check its availability.
     *
     * @param \WP_REST_Request $request The REST request object.
     * @return \WP_REST_Response The REST response object.
     */
    public function ping(\WP_REST_Request $request): \WP_REST_Response
    {
        // Send a GET request to the API.
        $response = wp_remote_get(Strings::PingEndpoint, [
            'timeout' => $this->ping_timeout, // Set the timeout for the request.
        ]);

        // Check if there was an error in the response.
        if (is_wp_error($response)) {
            return new \WP_REST_Response([
                'success' => 'false',
                'message' => 'Error pinging API',
            ], 500);
        }

        // Retrieve and decode the response body.
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);
        return new \WP_REST_Response($json, 200);
    }

    public function remove_posts_from_index(array $post_ids): array
    {
        // Call ThinkPixel API using POST.
        $start_time = microtime(true);
        $response = wp_remote_post(Strings::SearchEndpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_jwt(),
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'ids' => $post_ids,
            ]),
            'timeout' => $this->post_timeout, // Set the timeout for the request.
        ]);
        $this->last_latency = microtime(true) - $start_time;

        // Handle errors in the response.
        if (is_wp_error($response)) {
            error_log('Error calling ' . Strings::PluginName . ' API: ' . $response->get_error_message());
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        error_log('Remove posts from index response: ' . var_export($data, true));

        // Maybe the API returns the IDs of the posts that were removed.
        return $post_ids;
    }

    /**
     * Gets the last error that occurred.
     *
     * @return Error|null The last error or null if no error occurred.
     */
    public function get_last_error(): ?Error
    {
        return $this->last_error;
    }

    /**
     * Gets the last latency measured.
     *
     * @return float|null The last latency or null if no latency was measured.
     */
    public function get_last_latency(): ?float
    {
        return $this->last_latency;
    }

    /**
     * Cleans up by deleting the JWT transient.
     */
    public function cleanup(): void
    {
        delete_transient(Strings::JWTTransient);
    }
}
