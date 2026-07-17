<?php

namespace Tests\Unit\Libs\ThirdParty\JucaiDT;

use App\Libs\Exceptions\BadRequestException;
use App\Libs\ThirdParty\Application as ThirdPartyApplication;
use App\Libs\ThirdParty\JucaiDT\Api\ApiRequest;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ApiRequestTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_refreshes_token_and_retries_original_request_when_token_expired(): void
    {
        Cache::flush();

        $calls = [];
        $responses = [
            ['code' => 1, 'data' => ['access_token' => 'stale-token', 'expires_in' => 3600]],
            ['errorcode' => 10004, 'errormsg' => 'token expired'],
            ['code' => 1, 'data' => ['access_token' => 'fresh-token', 'expires_in' => 3600]],
            ['code' => 1, 'data' => ['items' => [1, 2, 3]]],
        ];

        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('requestAsync')
            ->times(4)
            ->andReturnUsing(function (string $method, string $endpoint, array $params) use (&$calls, &$responses) {
                $calls[] = compact('method', 'endpoint', 'params');
                $payload = array_shift($responses);

                return Create::promiseFor(new Response(200, [], json_encode($payload, JSON_THROW_ON_ERROR)));
            });

        $apiRequest = $this->makeApiRequest($client);
        $promise = $apiRequest->request('POST', '/resume/list', ['page' => 1]);
        $result = $apiRequest->response($promise);

        $this->assertSame([1, 2, 3], $result['data']['items']);
        $this->assertSame('/api/auth/login', $calls[0]['endpoint']);
        $this->assertIsObject($calls[0]['params']['json']);
        $this->assertSame([], get_object_vars($calls[0]['params']['json']));
        $this->assertSame('/api/resume/list', $calls[1]['endpoint']);
        $this->assertSame('/api/auth/login', $calls[2]['endpoint']);
        $this->assertSame('/api/resume/list', $calls[3]['endpoint']);
        $cacheKey = 'thirdparty:'.ApiRequest::class.':token:'.md5('https://example.com|test-app-key');
        $this->assertSame('fresh-token', Cache::get($cacheKey)['access_token']);
    }

    public function test_it_throws_after_three_failed_token_refresh_attempts(): void
    {
        Cache::flush();

        $responses = [
            ['code' => 1, 'data' => ['access_token' => 'stale-token', 'expires_in' => 3600]],
            ['errorcode' => 10004, 'errormsg' => 'token expired'],
            ['code' => 0, 'msg' => 'login failed'],
            ['code' => 0, 'msg' => 'login failed'],
            ['code' => 0, 'msg' => 'login failed'],
        ];

        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('requestAsync')
            ->times(5)
            ->andReturnUsing(function (string $method, string $endpoint, array $params) use (&$responses) {
                $payload = array_shift($responses);

                return Create::promiseFor(new Response(200, [], json_encode($payload, JSON_THROW_ON_ERROR)));
            });

        $apiRequest = $this->makeApiRequest($client);
        $promise = $apiRequest->request('POST', '/resume/list', ['page' => 1]);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('login failed');
        $apiRequest->response($promise);
    }

    public function test_it_removes_existing_api_prefix_before_reappending_it(): void
    {
        Cache::flush();

        $calls = [];
        $responses = [
            ['code' => 1, 'data' => ['access_token' => 'token', 'expires_in' => 3600]],
            ['code' => 1, 'data' => []],
        ];

        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('requestAsync')
            ->twice()
            ->andReturnUsing(function (string $method, string $endpoint, array $params) use (&$calls, &$responses) {
                $calls[] = compact('method', 'endpoint', 'params');
                $payload = array_shift($responses);

                return Create::promiseFor(new Response(200, [], json_encode($payload, JSON_THROW_ON_ERROR)));
            });

        $apiRequest = $this->makeApiRequest($client);
        $promise = $apiRequest->request('POST', '/api/resume/list', ['page' => 1]);
        $apiRequest->response($promise);

        $this->assertSame('/api/auth/login', $calls[0]['endpoint']);
        $this->assertSame('/api/resume/list', $calls[1]['endpoint']);
    }

    private function makeApiRequest(ClientInterface $client): ApiRequest
    {
        $application = new ThirdPartyApplication(app(), [
            'host' => 'https://example.com',
            'app_key' => 'test-app-key',
            'app_secret' => 'test-app-secret',
        ]);

        return new class($application, $client) extends ApiRequest
        {
            public function __construct(ThirdPartyApplication $app, private ClientInterface $client)
            {
                parent::__construct($app);
            }

            public function client(): ClientInterface
            {
                return $this->client;
            }
        };
    }
}
