<?php

/**
 * Core of *
 */

namespace ThinkPixel\Core;

/**
 * Cron Class. The ThinkPixel plugin uses this for scheduling cron jobs.
 *
 * @category ThinkPixel
 * @package ThinkPixel
 * @subpackage Core
 * @copyright
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 1.0.0
 */
class Cron
{
    const HookName = Strings::Plugin . '_cron_hook';
    const JobName = Strings::Plugin . '_cron_job';

    private $job;

    /**
     * Constructor for the Cron class.
     *
     * @param callable $job The job to run.
     */
    public function __construct($job)
    {
        $this->job = $job;

        add_action(self::HookName, [$this, 'cron_job']);
    }

    /**
     * Schedules the cron job to run hourly.
     */
    public function schedule_cron_job()
    {
        if (wp_next_scheduled(self::JobName) === false) {
            wp_schedule_event(time(), 'hourly', self::JobName);
        }
    }

    /**
     * Unschedules the cron job.
     */
    public function unschedule_cron_job()
    {
        $timestamp = wp_next_scheduled(self::JobName);
        if ($timestamp !== false) {
            wp_unschedule_event($timestamp, self::JobName);
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
