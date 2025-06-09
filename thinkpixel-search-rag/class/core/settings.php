<?php

/**
 * Core of ThinkPixel Plugin
 */

namespace ThinkPixel\Core;

/**
 * Settings Class. The ThinkPixel plugin uses this for managing settings.
 *
 * @category ThinkPixel
 * @package ThinkPixel
 * @subpackage Core
 * @copyright
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 1.1.0
 */
class Settings
{
    /**
     * Stores the API key securely in the database.
     *
     * @param string $api_key The API key to store.
     * @return void
     */
    function store_api_key(string $api_key): void
    {
        $encryption_key = hash('sha256', AUTH_KEY . SECURE_AUTH_KEY);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        $encrypted_key = openssl_encrypt($api_key, 'aes-256-cbc', $encryption_key, 0, $iv);
        $stored_value = base64_encode($iv . '::' . $encrypted_key);

        update_option(Strings::ApiKeyOption, $stored_value);
    }

    /**
     * Checks if an API key is stored in the database.
     *
     * @return bool True if an API key is stored, false otherwise.
     */
    function has_api_key(): bool
    {
        return (bool) get_option(Strings::ApiKeyOption);
    }

    /**
     * Retrieves the stored API key from the database.
     *
     * @return string|null The decrypted API key or null if not found.
     */
    function get_api_key(): ?string
    {
        return 'api-key-5JVg_fxeW0YGda8-IeHugX8-';

        $stored_value = get_option(Strings::ApiKeyOption);
        if (!$stored_value) return null;

        $encryption_key = hash('sha256', AUTH_KEY . SECURE_AUTH_KEY);
        list($iv, $encrypted_key) = explode('::', base64_decode($stored_value), 2);

        return openssl_decrypt($encrypted_key, 'aes-256-cbc', $encryption_key, 0, $iv);
    }

    /**
     * Stores the validation token and its expiration time.
     *
     * @param string $validation_token The validation token to store.
     * @param string $validation_token_expires_at The expiration time of the validation token in ISO 8601 format.
     * @return void
     */
    public function store_validation_token(string $validation_token, string $validation_token_expires_at): void
    {
        $expires_at = \DateTime::createFromFormat(\DateTime::ATOM, substr($validation_token_expires_at, 0, 19) . 'Z');
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $expires_in = $expires_at->getTimestamp() - $now->getTimestamp();
        set_transient(Strings::ValidationTokenTransient, $validation_token, $expires_in);
    }

    /**
     * Retrieves the stored validation token.
     *
     * @return string The validation token.
     */
    public function get_validation_token(): string
    {
        return get_transient(Strings::ValidationTokenTransient);
    }

    public function cleanup(): void
    {
        // Keep the API key when uninstalling the plugin.
        // delete_option(Strings::ApiKeyOption);
        delete_transient(Strings::ValidationTokenTransient);
    }
}
