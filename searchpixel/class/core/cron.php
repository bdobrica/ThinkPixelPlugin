<?php

/**
 * Core of *
 */

namespace SearchPixel\Core;

/**
 * Cron Class. The SearchPixel plugin uses this for scheduling cron jobs.
 *
 * @category SearchPixel
 * @package SearchPixel
 * @subpackage Core
 * @copyright
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 1.4.0
 */
class Cron
{
    const HookName = Strings::Plugin . '_cron_hook';

    private $job;

    /**
     * Constructor for the Cron class.
     *
     * @param callable $job The job to run.
     */
    public function __construct($job)
    {
        $this->job = $job;

        add_action(self::HookName, [$this, 'do_cron_job']);
    }

    /**
     * Schedules the cron job to run hourly.
     */
    public function schedule_cron_job()
    {
        if (wp_next_scheduled(self::HookName) === false) {
            wp_schedule_event(time(), 'hourly', self::HookName);
        }
    }

    /**
     * Unschedules the cron job.
     */
    public function unschedule_cron_job()
    {
        $timestamp = wp_next_scheduled(self::HookName);
        if ($timestamp !== false) {
            wp_unschedule_event($timestamp, self::HookName);
        }
    }

    /**
     * Executes the cron job to process unprocessed posts.
     */
    public function do_cron_job()
    {
        if (is_callable($this->job)) {
            call_user_func($this->job);
        }
    }
}
