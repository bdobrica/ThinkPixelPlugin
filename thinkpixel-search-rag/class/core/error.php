<?php

/**
 * Core of *
 */

namespace ThinkPixel\Core;

/**
 * Error class. Used to log errors.
 *
 * @category ThinkPixel
 * @package ThinkPixel
 * @subpackage Core
 * @copyright
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 1.2.0
 */
class Error
{
    private $object;
    private $method;
    private $message;

    /**
     * Constructor for the Error class.
     *
     * @param string $object The object where the error occurred.
     * @param string $method The method where the error occurred.
     * @param string $message The error message.
     */
    public function __construct(string $object, string $method, string $message = 'An error occurred.')
    {
        $this->object = $object;
        $this->method = $method;
        $this->message = $message;
    }

    /**
     * Converts the error object to an array.
     *
     * @return array The error details as an array.
     */
    public function __toArray()
    {
        return [
            'object' => $this->object,
            'method' => $this->method,
            'message' => $this->message,
        ];
    }

    /**
     * Converts the error object to a string.
     *
     * @return string The error details as a string.
     */
    public function __toString()
    {
        return sprintf('Error in %s::%s: %s', $this->object, $this->method, $this->message);
    }

    /**
     * Logs the error message to the error log.
     */
    public function log()
    {
        error_log((string) $this);
    }
}
