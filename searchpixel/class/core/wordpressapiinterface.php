<?php

/**
 * Core of *
 */

namespace SearchPixel\Core;

/**
 * Class WordPressApiInterface
 *
 * @category SearchPixel
 * @package SearchPixel
 * @subpackage Core
 * @copyright
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 1.4.1
 */
interface WordPressApiInterface
{
    public function getSiteUrl(): string;

    public function remotePost(string $url, array $args = []);

    public function remoteGet(string $url, array $args = []);

    public function isWpError(mixed $thing): bool;

    public function remoteRetrieveBody($response): string;

    public function getPost(int $postId);

    public function applyFilters(string $hookName, mixed $value): mixed;

    public function setTransient(string $name, mixed $value, int $expiration): bool;

    public function getTransient(string $name): mixed;

    public function deleteTransient(string $name): bool;

    public function errorLog(string $message): void;
}
