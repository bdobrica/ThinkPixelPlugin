<?php

use PHPUnit\Framework\TestCase;
use ThinkPixel\Core\Db;

interface WPDBMock
{
    public function get_charset_collate();
    public function query($sql);
    public function tables();
    public function db_version();
    public function db_server_info();
    public function suppress_errors();
    public function get_results($sql);
}

class DbTest extends TestCase
{
    private $wpdbMock;
    private $db;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a mock of the wpdb global object
        $this->wpdbMock = $this->createMock(WPDBMock::class);

        // Set up expected properties
        $this->wpdbMock->prefix = 'wp_';
        $this->wpdbMock->method('get_charset_collate')->willReturn('utf8mb4');
        $this->wpdbMock->method('tables')->willReturn([]);

        // Assign the mock to the global $wpdb
        $GLOBALS['wpdb'] = $this->wpdbMock;

        // Instantiate the Db class
        $this->db = new Db();
    }

    protected function tearDown(): void
    {
        // Clean up the global $wpdb after each test
        unset($GLOBALS['wpdb']);
        parent::tearDown();
    }

    public function testGetTableName()
    {
        $this->assertEquals('wp_thinkpixel', $this->db->get_table_name());
    }

    public function testCreateTable()
    {
        $this->wpdbMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('CREATE TABLE'));

        $this->db->create_table();
    }

    public function testDropTable()
    {
        $this->wpdbMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DROP TABLE IF EXISTS'));

        $this->db->drop_table();
    }
}
