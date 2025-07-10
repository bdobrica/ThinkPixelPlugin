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
 * @version 1.3.1
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
    const PluginVersion = '1.3.1';

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
    const Domain = 'thinkpixel-search-rag';

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
     * The endpoint for retrieving the maximum batch text size.
     */
    const MaxBatchTextSizeEndpoint = self::ApiUrl . '/store/max_batch_text_size';

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
     * The transient key for maximum batch text size.
     */
    const MaxBatchTextSizeTransient = self::Plugin . '_max_batch_text_size';

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
    const ApiNoticeTransient = self::Plugin . '_api_notice';

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
}
