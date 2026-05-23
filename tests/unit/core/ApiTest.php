<?php

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SearchPixel\Core\Api;
use SearchPixel\Core\Db;
use SearchPixel\Core\Strings;
use SearchPixel\Core\WordPressApiInterface;

class ApiTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $api;
    private $wordpress;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wordpress = Mockery::mock(WordPressApiInterface::class);
        $this->api = new Api(fn(): string => 'test-api-key', $this->wordpress);
    }

    public function testProcessPostsMarksRowsProcessedAfterSuccessfulApiCall()
    {
        $response = ['response' => ['code' => 200]];

        $db = Mockery::mock(Db::class);
        $db->shouldReceive('get_unprocessed_posts')->once()->with(10)->andReturn([1, 2]);
        $db->shouldReceive('mark_processed_posts')->once()->with([1, 2]);

        $this->wordpress->shouldReceive('getTransient')->once()->with(Strings::MaxBatchTextSizeTransient)->andReturn(4096);
        $this->wordpress->shouldReceive('getTransient')->once()->with(Strings::JWTTransient)->andReturn('jwt-token');
        $this->wordpress->shouldReceive('getPost')->once()->with(1)->andReturn((object) [
            'ID' => 1,
            'post_title' => 'Title 1',
            'post_content' => 'Content 1',
            'post_type' => 'post',
        ]);
        $this->wordpress->shouldReceive('getPost')->once()->with(2)->andReturn((object) [
            'ID' => 2,
            'post_title' => 'Title 2',
            'post_content' => 'Content 2',
            'post_type' => 'page',
        ]);
        $this->wordpress->shouldReceive('applyFilters')->times(4)->andReturnUsing(function ($hookName, $value) {
            return $value;
        });
        $this->wordpress->shouldReceive('remotePost')->once()->with(
            Strings::StoreEndpoint,
            Mockery::on(function (array $request) {
                $payload = json_decode($request['body'], true);

                return $request['headers']['Authorization'] === 'Bearer jwt-token'
                    && $request['headers']['Content-Type'] === 'application/json'
                    && count($payload) === 2
                    && $payload[0]['id'] === 1
                    && $payload[1]['id'] === 2;
            })
        )->andReturn($response);
        $this->wordpress->shouldReceive('isWpError')->once()->with($response)->andReturn(false);
        $this->wordpress->shouldReceive('remoteRetrieveBody')->once()->with($response)->andReturn(json_encode([
            'stored_ids' => [1, 2],
        ]));

        $processedIds = $this->api->process_posts($db);

        $this->assertSame([1, 2], $processedIds);
    }

    public function testProcessPostsReturnsEmptyArrayWhenNoPostsNeedProcessing()
    {
        $db = Mockery::mock(Db::class);
        $db->shouldReceive('get_unprocessed_posts')->once()->with(10)->andReturn([]);
        $db->shouldNotReceive('mark_processed_posts');

        $this->assertSame([], $this->api->process_posts($db));
    }

    public function testProcessPostsLogsApiErrorsWithoutMarkingPostsProcessed()
    {
        $response = new WP_Error('api_error', 'Failed to connect');

        $db = Mockery::mock(Db::class);
        $db->shouldReceive('get_unprocessed_posts')->once()->with(10)->andReturn([1]);
        $db->shouldNotReceive('mark_processed_posts');

        $this->wordpress->shouldReceive('getTransient')->once()->with(Strings::MaxBatchTextSizeTransient)->andReturn(4096);
        $this->wordpress->shouldReceive('getTransient')->once()->with(Strings::JWTTransient)->andReturn('jwt-token');
        $this->wordpress->shouldReceive('getPost')->once()->with(1)->andReturn((object) [
            'ID' => 1,
            'post_title' => 'Error Test',
            'post_content' => 'Content',
            'post_type' => 'post',
        ]);
        $this->wordpress->shouldReceive('applyFilters')->times(2)->andReturnUsing(function ($hookName, $value) {
            return $value;
        });
        $this->wordpress->shouldReceive('remotePost')->once()->andReturn($response);
        $this->wordpress->shouldReceive('isWpError')->once()->with($response)->andReturn(true);
        $this->wordpress->shouldReceive('errorLog')->once()->with('SearchPixel API error: Failed to connect');

        $processedIds = $this->api->process_posts($db);

        $this->assertSame([], $processedIds);
    }
}
