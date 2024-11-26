<?php

use PHPUnit\Framework\TestCase;
use ThinkPixel\Core\Api;

interface CallableMock
{
    public function __invoke();
}

class ApiTest extends TestCase
{
    private $api;

    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('runkit7')) {
            $this->markTestSkipped('The runkit7 extension is not loaded.');
        }

        // Mock add_action function in the ThinkPixel\Core namespace
        $mockAddAction = $this->createMock(CallableMock::class);
        $mockAddAction
            ->expects($this->once())
            ->method('__invoke')
            ->willReturnCallback(function ($hook, $callback) {
                $callback();
            });

        // Override the add_action function
        $this->overrideFunction('ThinkPixel\Core\add_action', $mockAddAction);

        // Create an instance of the Api class
        $this->api = new Api();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testConstructorAddsAction()
    {
        // Trigger the constructor to verify the action is added
        new Api();
    }

    public function testProcessPostsWithSuccessfulApiCall()
    {
        // Mock the WordPress functions
        $mockGetPost = $this->createMock(CallableMock::class);
        $mockGetPost
            ->method('__invoke')
            ->willReturnCallback(function ($postId) {
                return (object)[
                    'ID' => $postId,
                    'post_title' => "Title {$postId}",
                    'post_content' => "Content {$postId}",
                    'post_type' => 'post',
                ];
            });

        $mockApplyFilters = $this->createMock(CallableMock::class);
        $mockApplyFilters
            ->method('__invoke')
            ->willReturnCallback(function ($filterName, $content) {
                return "<p>Filtered {$content}</p>";
            });

        // Mock the HTTP request
        $mockWpRemotePost = $this->createMock(CallableMock::class);
        $mockWpRemotePost
            ->method('__invoke')
            ->willReturn(['response' => ['code' => 200]]);

        // Override the WordPress functions
        $this->overrideFunction('ThinkPixel\Core\get_post', $mockGetPost);
        $this->overrideFunction('ThinkPixel\Core\apply_filters', $mockApplyFilters);
        $this->overrideFunction('ThinkPixel\Core\wp_remote_post', $mockWpRemotePost);

        // Run the method
        $processedIds = $this->api->process_posts([1, 2]);

        // Assert that the processed IDs are returned correctly
        $this->assertEquals([1, 2], $processedIds);
    }

    public function testProcessPostsWithApiError()
    {
        // Mock the WordPress functions and simulate API error
        $mockGetPost = $this->createMock(CallableMock::class);
        $mockGetPost
            ->method('__invoke')
            ->willReturn((object)[
                'ID' => 1,
                'post_title' => 'Error Test',
                'post_content' => 'Content',
                'post_type' => 'post',
            ]);

        $mockApplyFilters = $this->createMock(CallableMock::class);
        $mockApplyFilters
            ->method('__invoke')
            ->willReturn('<p>Filtered Content</p>');

        $mockWpRemotePost = $this->createMock(CallableMock::class);
        $mockWpRemotePost
            ->method('__invoke')
            ->willReturn(new WP_Error('api_error', 'Failed to connect'));

        $mockErrorLog = $this->createMock(CallableMock::class);
        $mockErrorLog
            ->expects($this->once())
            ->method('__invoke')
            ->with('ThinkPixel API error: Failed to connect');

        // Override the WordPress functions
        $this->overrideFunction('ThinkPixel\Core\get_post', $mockGetPost);
        $this->overrideFunction('ThinkPixel\Core\apply_filters', $mockApplyFilters);
        $this->overrideFunction('ThinkPixel\Core\wp_remote_post', $mockWpRemotePost);
        $this->overrideFunction('ThinkPixel\Core\error_log', $mockErrorLog);

        // Run the method
        $processedIds = $this->api->process_posts([1]);

        // Assert that no IDs are returned
        $this->assertNull($processedIds);
    }

    private function overrideFunction($functionName, $mock)
    {
        if (function_exists($functionName)) {
            runkit7_function_redefine(
                $functionName,
                function () use ($mock) {
                    $mock(...func_get_args());
                }
            );
        } else {
            runkit7_function_add(
                $functionName,
                function () use ($mock) {
                    $mock(...func_get_args());
                }
            );
        }

        if (!function_exists($functionName)) {
            $this->markTestSkipped("Failed to override {$functionName}");
        }
    }
}
