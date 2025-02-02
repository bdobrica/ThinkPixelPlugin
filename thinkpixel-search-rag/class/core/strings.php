<?php

/**
 * Core of *
 */

namespace ThinkPixel\Core;

/**
 * Strings Class. The ThinkPixel plugin uses this for encapsulating strings.
 *
 * @category ThinkPixel
 * @package ThinkPixel
 * @subpackage Core
 * @copyright
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 1.0.0
 */
class Strings
{
    /**
     * The name of the plugin.
     */
    const PluginName = 'ThinkPixel Search RAG';

    /**
     * The version of the plugin.
     */
    const PluginVersion = '1.0.0';

    /**
     * The plugin identifier.
     */
    const Plugin = 'thinkpixel_search_rag';

    /**
     * The plugin identifier slug.
     */
    const PluginSlug = 'thinkpixel-search-rag';

    /**
     * The short plugin identifier slug.
     */
    const PluginShortSlug = 'thinkpixel';

    /**
     * The name of the database table used by the plugin.
     */
    const PageLogTable = self::PluginShortSlug . '_page_log';

    /**
     * The name of the database table used for caching search results.
     */
    const SearchCacheTable = self::PluginShortSlug . '_search_cache';

    /**
     * The base URL for the ThinkPixel API.
     */
    const ApiUrl = 'https://api.thinkpixel.io:8080';

    /**
     * The translation domain for the plugin.
     */
    const Domain = self::PluginSlug;

    /**
     * The endpoint for fetching the authentication token.
     */
    const AuthTokenEndpoint = self::ApiUrl . '/auth/token';

    /**
     * The endpoint for pinging the API.
     */
    const PingEndpoint = self::ApiUrl . '/ping';

    /**
     * The endpoint for registering the site.
     */
    const RegisterEndpoint = self::ApiUrl . '/register';

    /**
     * The endpoint for performing searches.
     */
    const SearchEndpoint = self::ApiUrl . '/search';

    /**
     * The endpoint for storing data.
     */
    const StoreEndpoint = self::ApiUrl . '/store';

    /**
     * The endpoint for removing item embeddings.
     */
    const RemoveItemsFromIndexEndpoint = self::ApiUrl . '/remove/embeddings';

    /**
     * The endpoint for regenerating the API key.
     */
    const RefreshApiKeyEndpoint = self::ApiUrl . '/refresh/apikey';

    /**
     * The transient key for storing the JWT token.
     */
    const JWTTransient = self::Plugin . '_jwt';

    /**
     * The plugin rest namespace.
     */
    const RestNamespace = self::PluginShortSlug . '/v1';

    /**
     * The rest route for validating the current website.
     */
    const ValidateRoute = 'validate';

    /**
     * The rest route for exchanging the validation token for an API key.
     */
    const ExchangeRoute = 'exchange';

    /**
     * The rest route for pinging the API.
     */
    const PingRoute = 'ping';

    /**
     * The rest route for bulk processing.
     */
    const BulkProcessRoute = 'bulk-process';

    /**
     * The rest route for skipping search.
     */
    const SkipSearchRoute = 'skip-search';

    /**
     * The rest route for debugging report.
     */
    const DebugRoute = 'debug';

    /**
     * Nonce for API key actions.
     */
    const ApiKeyNonce = self::Plugin . '_api_key_nonce';

    /**
     * Nonce for skipping pages actions.
     */
    const SkipItemsNonce = self::Plugin . '_skip_items_nonce';

    /**
     * Action for managing the API key.
     */
    const ApiKeyAction = self::Plugin . '_api_key_action';

    /**
     * Action for managing the skipped pages.
     */
    const SkipItemsAction = self::Plugin . '_skip_items_action';

    /**
     * Nonce for key exchange actions.
     */
    const KeyExchangeNonce = self::Plugin . '_key_exchange_nonce';

    /**
     * Transient key for storing API error messages.
     */
    const ApiErrorTransient = self::Plugin . '_api_error';

    /**
     * Option key for storing the API key.
     */
    const ApiKeyOption = self::Plugin . '_api_key';

    /**
     * Handle for the settings JavaScript file.
     */
    const SettingsJS = self::PluginSlug . '-settings-js';

    /**
     * JavaScript object for the settings page.
     */
    const SettingsJSObject = self::PluginShortSlug . 'Settings';

    /**
     * Handle for the settings CSS file.
     */
    const SettingsCSS = self::PluginSlug . '-settings-css';

    /**
     * Slug for the settings page.
     */
    const SettingsSlug = self::PluginSlug . '-settings';

    /**
     * Transient key for storing the validation token.
     */
    const ValidationTokenTransient = self::Plugin . '_validation_token';

    /**
     * Collapse repeated whitespace:
     * - If the group of whitespace contains at least one line break
     *      (\n, \r, or \r\n), it is replaced with a single newline (\n).
     * - If the group of whitespace does not contain any line break,
     *      it is replaced with a single space ( ).
     *
     * @param string $text The input text.
     * @return string The text with collapsed whitespace.
     */
    public static function collapse_whitespace($text)
    {
        $text = preg_replace_callback(
            '/\s+/', // Matches one or more whitespace characters
            function ($matches) {
                $whitespace = $matches[0];
                // Check if there's any line break in this group
                if (preg_match('/[\r\n]/', $whitespace)) {
                    // If so, replace the entire group with one newline
                    return "\n";
                }
                // Otherwise, replace with a single space
                return ' ';
            },
            $text
        );

        return trim($text);
    }

    /**
     * Converts HTML content to plain text.
     *
     * @param string $html The HTML content.
     * @return string The plain text content.
     */
    public static function wp_html_to_plain_text(string $html): string
    {
        // 1) Convert <img> tags:
        //    (image about <alt|title>) if either alt or title is present, otherwise (image).
        $html = preg_replace_callback(
            '/<img\b[^>]*>/i',
            function ($matches) {
                $img_tag = $matches[0];

                // Extract alt attribute
                $alt = '';
                if (preg_match('/alt\s*=\s*"([^"]*)"/i', $img_tag, $alt_matches)) {
                    $alt = trim($alt_matches[1]);
                }
                // Extract title attribute
                $title = '';
                if (preg_match('/title\s*=\s*"([^"]*)"/i', $img_tag, $title_matches)) {
                    $title = trim($title_matches[1]);
                }

                if (! empty($alt)) {
                    return '(image about ' . $alt . ')';
                } elseif (! empty($title)) {
                    return '(image about ' . $title . ')';
                } else {
                    return '(image)';
                }
            },
            $html
        );

        // 2) Convert <a> tags:
        //    (link to <title>) if the title attribute is present, otherwise (link).
        $html = preg_replace_callback(
            '/<a\b[^>]*>(.*?)<\/a>/is',
            function ($matches) {
                $a_tag_content = $matches[0];
                // Extract title attribute
                $title = '';
                if (preg_match('/title\s*=\s*"([^"]*)"/i', $a_tag_content, $title_matches)) {
                    $title = trim($title_matches[1]);
                }

                if (! empty($title)) {
                    return '(link to ' . $title . ')';
                } else {
                    return '(link)';
                }
            },
            $html
        );

        // 3) Remove all remaining HTML tags to get plain text.
        $text_output = strip_tags($html);

        // 4) Convert special HTML entities (e.g., &#8217;) to their corresponding Unicode characters.
        //    This handles both numeric and named entities.
        $text_output = html_entity_decode($text_output, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 5) Normalize whitespace
        //    - Any sequence of spaces/tabs becomes a single space
        //    - Each line-break character (\r, \n, \r\n) becomes a single \n
        $text_output = self::collapse_whitespace($text_output);

        return $text_output;
    }
}
