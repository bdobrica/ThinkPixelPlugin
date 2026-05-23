<?php

use PHPUnit\Framework\TestCase;
use SearchPixel\Core\Db;

class WPDBDouble
{
    public $prefix = 'wp_';
    public $posts = 'wp_posts';

    public function query($sql) {}

    public function get_results($sql)
    {
        return [];
    }

    public function prepare($sql, $args)
    {
        return $sql;
    }
}

class DbTest extends TestCase
{
    private $originalWpdb;
    private $wpdbMock;
    private $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalWpdb = $GLOBALS['wpdb'] ?? null;

        $this->wpdbMock = $this->getMockBuilder(WPDBDouble::class)
            ->onlyMethods(['query', 'get_results', 'prepare'])
            ->getMock();

        $GLOBALS['wpdb'] = $this->wpdbMock;
        $this->db = new Db();
    }

    protected function tearDown(): void
    {
        $GLOBALS['wpdb'] = $this->originalWpdb;
        parent::tearDown();
    }

    public function testGetPostLogsTableName()
    {
        $this->wpdbMock->expects($this->never())
            ->method('query');

        $this->assertSame('wp_searchpixel_page_log', $this->db->get_post_logs_table_name());
    }

    public function testGetSearchCacheTableName()
    {
        $this->wpdbMock->expects($this->never())
            ->method('query');

        $this->assertSame('wp_searchpixel_search_cache', $this->db->get_search_cache_table_name());
    }

    public function testDropTablesDropsBothCustomTables()
    {
        $this->wpdbMock->expects($this->once())
            ->method('query')
            ->with('DROP TABLE IF EXISTS `wp_searchpixel_page_log`, `wp_searchpixel_search_cache`');

        $this->db->drop_tables();
    }

    public function testGetUnprocessedPostsReturnsIntegerIds()
    {
        $this->wpdbMock->expects($this->once())
            ->method('get_results')
            ->with('SELECT post_id FROM `wp_searchpixel_page_log` WHERE processed_flag = 0 LIMIT 3')
            ->willReturn([
                (object) ['post_id' => '7'],
                (object) ['post_id' => 9],
            ]);

        $this->assertSame([7, 9], $this->db->get_unprocessed_posts(3));
    }

    public function testMarkProcessedPostsPreparesAndExecutesUpdate()
    {
        $preparedSql = 'UPDATE `wp_searchpixel_page_log` SET processed_flag = 1 WHERE post_id IN (4,9)';

        $this->wpdbMock->expects($this->once())
            ->method('prepare')
            ->with('UPDATE `wp_searchpixel_page_log` SET processed_flag = 1 WHERE post_id IN (%d,%d)', [4, 9])
            ->willReturn($preparedSql);

        $this->wpdbMock->expects($this->once())
            ->method('query')
            ->with($preparedSql);

        $this->db->mark_processed_posts([4, 9]);
    }
}
