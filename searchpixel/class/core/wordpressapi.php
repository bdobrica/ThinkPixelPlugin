<?php

/**
 * Core of *
 */

namespace SearchPixel\Core;

/**
 * Class WordPressApi
 *
 * @category SearchPixel
 * @package SearchPixel
 * @subpackage Core
 * @copyright
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 1.4.4
 */
class WordPressApi implements WordPressApiInterface
{
    public function getSiteUrl(): string
    {
        return get_site_url();
    }

    public function remotePost(string $url, array $args = [])
    {
        return wp_remote_post($url, $args);
    }

    public function remoteGet(string $url, array $args = [])
    {
        return wp_remote_get($url, $args);
    }

    public function isWpError(mixed $thing): bool
    {
        return is_wp_error($thing);
    }

    public function remoteRetrieveBody($response): string
    {
        return wp_remote_retrieve_body($response);
    }

    public function getPost(int $postId)
    {
        return get_post($postId);
    }

    public function applyFilters(string $hookName, mixed $value): mixed
    {
        return apply_filters($hookName, $value);
    }

    public function setTransient(string $name, mixed $value, int $expiration): bool
    {
        return set_transient($name, $value, $expiration);
    }

    public function getTransient(string $name): mixed
    {
        return get_transient($name);
    }

    public function deleteTransient(string $name): bool
    {
        return delete_transient($name);
    }

    public function errorLog(string $message): void
    {
        error_log($message);
    }
}
