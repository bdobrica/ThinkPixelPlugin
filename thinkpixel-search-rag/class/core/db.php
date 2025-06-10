<?php

/**
 * Core of *
 */

namespace ThinkPixel\Core;

/**
 * Db Class. The ThinkPixel plugin relies on this for database operations.
 *
 * @category ThinkPixel
 * @package ThinkPixel
 * @subpackage Core
 * @copyright
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 1.1.2
 */
class Db
{
    private $wpdb; // WordPress Database Object
    private $cache_expiry_time; // Cache expiry time in seconds

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->cache_expiry_time = 3600; // 1 hour
    }

    /**
     * Get the table name for post logs.
     *
     * @return string
     */
    public function get_post_logs_table_name(): string
    {
        return $this->wpdb->prefix . Strings::PageLogTable;
    }

    /**
     * Get the table name for search cache.
     *
     * @return string
     */
    public function get_search_cache_table_name(): string
    {
        return $this->wpdb->prefix . Strings::SearchCacheTable;
    }

    /**
     * Create the necessary tables.
     *
     * @return void
     */
    public function create_tables(): void
    {
        $charset_collate = $this->wpdb->get_charset_collate();

        $queries = [
            // SQL for creating the post logs table.
            'CREATE TABLE `' . $this->get_post_logs_table_name() . '` (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            skip_flag BOOLEAN DEFAULT FALSE,
            processed_flag BOOLEAN DEFAULT FALSE,
            last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id)
        ) ' . $charset_collate,
            // SQL for creating the search cache table.
            'CREATE TABLE `' . $this->get_search_cache_table_name() . '` (
            search_hash CHAR(64) PRIMARY KEY,
            search_query TEXT NOT NULL,
            results LONGTEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ' . $charset_collate
        ];

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($queries);
    }

    /**
     * Drop the necessary tables.
     *
     * @return void
     */
    public function drop_tables(): void
    {
        $tables = [
            $this->get_post_logs_table_name(),
            $this->get_search_cache_table_name()
        ];
        $sql = 'DROP TABLE IF EXISTS `' . implode('`, `', $tables) . '`';
        $this->wpdb->query($sql);
    }

    /**
     * Sync posts to the log table.
     *
     * @return void
     */
    public function sync_posts_to_log(): void
    {
        $post_logs_table = $this->get_post_logs_table_name();
        $wp_posts_table = $this->wpdb->posts;

        // We use INSERT IGNORE here so that if a post_id already exists in the
        // custom table, it won't cause a duplicate error (due to UNIQUE KEY).
        // skip_flag = 0 (FALSE), processed_flag = 0 (FALSE).
        $sql = "
            INSERT IGNORE INTO `{$post_logs_table}` (post_id, skip_flag, processed_flag)
            SELECT ID, 0 AS skip_flag, 0 AS processed_flag
            FROM `{$wp_posts_table}`
            WHERE post_type IN ('post','page')
              AND post_status = 'publish'
        ";

        $this->wpdb->query($sql);
    }

    /**
     * Drop the post logs table.
     *
     * @return void
     */
    public function drop_table(): void
    {
        $sql = 'DROP TABLE IF EXISTS ' . $this->get_post_logs_table_name();
        $this->wpdb->query($sql);
    }

    /**
     * Get unprocessed posts.
     *
     * @param int $limit
     * @return array
     */
    public function get_unprocessed_posts(int $limit = 10): array
    {
        $posts = $this->wpdb->get_results('SELECT post_id FROM `' . $this->get_post_logs_table_name() . '` WHERE processed_flag = 0 LIMIT ' . $limit);
        if (!$posts) return [];
        return array_map(fn($post) => (int) $post->post_id, $posts);
    }

    public function get_sample_post_id(): ?int
    {
        $post_id = $this->wpdb->get_var('SELECT post_id FROM `' . $this->get_post_logs_table_name() . '` WHERE skip_flag = 0 LIMIT 1');
        return $post_id ? (int) $post_id : null;
    }

    /**
     * Mark posts as processed.
     *
     * @param array $processed_ids
     * @return void
     */
    public function mark_processed_posts(array $processed_ids): void
    {
        $ids_placeholder = implode(',', array_fill(0, count($processed_ids), '%d'));
        $this->wpdb->query(
            $this->wpdb->prepare('UPDATE `' . $this->get_post_logs_table_name() . "` SET processed_flag = 1 WHERE post_id IN ($ids_placeholder)", $processed_ids)
        );
    }

    /**
     * Get the count of processed posts.
     *
     * @return int
     */
    public function get_processed_post_count(): int
    {
        return (int) $this->wpdb->get_var('SELECT COUNT(1) FROM `' . $this->get_post_logs_table_name() . '` WHERE processed_flag = 1');
    }

    /**
     * Get the count of unprocessed posts.
     *
     * @return int
     */
    public function get_unprocessed_post_count(): int
    {
        return (int) $this->wpdb->get_var('SELECT COUNT(1) FROM `' . $this->get_post_logs_table_name() . '` WHERE processed_flag = 0');
    }

    /**
     * Get the total count of posts.
     *
     * @return int
     */
    public function get_total_post_count(): int
    {
        return (int) $this->wpdb->get_var('SELECT COUNT(1) FROM `' . $this->get_post_logs_table_name() . '`');
    }

    /**
     * Update the skip status for a post. If the skip flag is true, the processed flag is also set to false.
     *
     * @param int $post_id
     * @param bool $skip_flag
     * @return void
     */
    public function update_skip_status_for_post(int $post_id, bool $skip_flag = true): void
    {
        if ((int) $skip_flag === 1) {
            $this->wpdb->update(
                $this->get_post_logs_table_name(),
                ['skip_flag' => $skip_flag, 'processed_flag' => 0],
                ['post_id' => $post_id],
                ['%d', '%d'],
                ['%d']
            );
            return;
        }
        $this->wpdb->update(
            $this->get_post_logs_table_name(),
            ['skip_flag' => $skip_flag],
            ['post_id' => $post_id],
            ['%d'],
            ['%d']
        );
    }

    /**
     * Update the skip status for multiple posts. If the skip flag is true, the processed flag is also set to false.
     *
     * @param array $post_ids
     * @param bool $skip_flag
     * @return void
     */
    public function update_skip_status_for_posts(array $post_ids, $skip_flag): void
    {
        $ids_placeholder = implode(',', array_fill(0, count($post_ids), '%d'));

        if ((int) $skip_flag === 1) {
            $sql = $this->wpdb->prepare(
                'UPDATE `' .
                    $this->get_post_logs_table_name() .
                    '` SET skip_flag = %d, processed_flag = 0' .
                    ' WHERE post_id IN (' . $ids_placeholder . ')',
                array_merge([$skip_flag], $post_ids)
            );
            $this->wpdb->query($sql);
            return;
        }

        $sql = $this->wpdb->prepare(
            'UPDATE `' .
                $this->get_post_logs_table_name() .
                '` SET skip_flag = %d' .
                ' WHERE post_id IN (' . $ids_placeholder . ')',
            array_merge([$skip_flag], $post_ids)
        );
        $this->wpdb->query($sql);
    }

    /**
     * Get posts skip status by keyword with pagination.
     *
     * @param string $keyword
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get_posts_skip_status_by_keyword(string $keyword = '', int $limit = -1, int $offset = 0): array
    {
        // Base SQL query
        $sql = "SELECT p.ID, p.post_title, l.skip_flag, l.processed_flag, l.last_updated
                FROM `" . $this->get_post_logs_table_name() . "` l
                JOIN `" . $this->wpdb->posts . "` p
                ON l.post_id = p.ID";

        // Add keyword condition if keyword is provided
        if (!empty($keyword)) {
            $sql .= $this->wpdb->prepare(" WHERE p.post_title LIKE %s", '%' . $this->wpdb->esc_like($keyword) . '%');
        }

        $count_sql = "SELECT COUNT(1) FROM (" . $sql . ") as count_table";
        $count = (int) $this->wpdb->get_var($count_sql);

        // Add pagination
        if ($limit > 0)
            $sql .= $this->wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);
        $results = $this->wpdb->get_results($sql, ARRAY_A);

        return [
            'offset' => $offset,
            'limit' => $limit,
            'count' => $count,
            'results' => $results
        ];
    }

    /**
     * Track a post.
     *
     * @param int $post_id
     * @return void
     */
    public function track_post(int $post_id): void
    {
        if (wp_is_post_revision($post_id)) return;

        $post_type = get_post_type($post_id);
        if ($post_type === 'post' || $post_type === 'page') {
            $this->wpdb->replace(
                $this->get_post_logs_table_name(),
                ['post_id' => $post_id, 'processed_flag' => false],
                ['%d', '%d']
            );
        }
    }

    /**
     * Trash a post.
     *
     * @param int $post_id
     * @return void
     */
    public function trash_post(int $post_id): void
    {
        $this->wpdb->delete($this->get_post_logs_table_name(), ['post_id' => $post_id], ['%d']);
    }

    /**
     * Calculate site statistics.
     *
     * @return array
     */
    public function calculate_site_stats(): array
    {
        $results = $this->wpdb->get_row("
            SELECT 
                COUNT(1) as total_pages, 
                AVG(CHAR_LENGTH(post_content)) as average_size, 
                STDDEV(CHAR_LENGTH(post_content)) as std_dev_size 
            FROM {$this->wpdb->posts}
            WHERE post_type IN ('post', 'page') AND post_status = 'publish'
        ");

        return [
            'total_pages' => (int) $results->total_pages,
            'average_size' => (int) $results->average_size,
            'std_dev_size' => (int) $results->std_dev_size,
        ];
    }

    /**
     * Cache search results.
     *
     * @param string $search_term The search term.
     * @param array $search_results The search results.
     * @return bool True if the record was saved, false otherwise.
     */
    public function cache_search_results(string $search_term, array $search_results): bool
    {
        // Hash the search term.
        $search_hash = hash('sha256', $search_term);

        // Calculate the expiration time.
        $expires_at = date('Y-m-d H:i:s', time() + $this->cache_expiry_time);

        // Prepare the data for insertion.
        $data = [
            'search_hash' => $search_hash,
            'search_query' => $search_term,
            'results' => json_encode($search_results),
            'expires_at' => $expires_at,
        ];

        // Insert the data into the search cache table.
        $inserted = $this->wpdb->insert(
            $this->get_search_cache_table_name(),
            $data,
            ['%s', '%s', '%s', '%s']
        );

        // Delete expired entries.
        $this->wpdb->query(
            'DELETE FROM `' . $this->get_search_cache_table_name() . '` WHERE expires_at < NOW()'
        );

        return $inserted !== false;
    }

    /**
     * Retrieve cached search results.
     *
     * @param string $search_term The search term.
     * @return array|null The cached search results or null if not found.
     */
    public function get_cached_search_results(string $search_term): ?array
    {
        // Hash the search term.
        $search_hash = hash('sha256', $search_term);

        // Query the search cache table for the cached results.
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                'SELECT results FROM `' . $this->get_search_cache_table_name() . '` WHERE search_hash = %s AND expires_at > NOW()',
                $search_hash
            ),
            ARRAY_A
        );

        // Return the cached results if found, otherwise return null.
        return $result ? json_decode($result['results'], true) : null;
    }
}
