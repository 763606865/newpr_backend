<?php

namespace Tests\Unit\SApi;

use App\Services\SApiSignatureService;
use Illuminate\Http\Request;
use Tests\TestCase;

class SApiSignatureServiceTest extends TestCase
{
    public function test_sign_is_stable_for_same_canonical_input(): void
    {
        $service = SApiSignatureService::make();

        $request = Request::create('/sapi/ping', 'GET', ['page' => '1', 'foo' => 'bar']);
        $request->headers->set($service->headerName('timestamp'), '1717488000');
        $request->headers->set($service->headerName('nonce'), 'abc123');

        $firstSign = $service->sign($request, 'sapi_test_key', 'test-secret');
        $secondSign = $service->sign($request, 'sapi_test_key', 'test-secret');

        $this->assertSame($firstSign, $secondSign);
        $this->assertTrue($service->verify($request, 'sapi_test_key', 'test-secret', $firstSign));
    }

    public function test_sign_changes_when_body_changes(): void
    {
        $service = SApiSignatureService::make();

        $requestA = Request::create('/sapi/demo', 'POST', [], [], [], [], '{"a":1}');
        $requestB = Request::create('/sapi/demo', 'POST', [], [], [], [], '{"a":2}');

        $requestA->headers->set($service->headerName('timestamp'), '1717488000');
        $requestA->headers->set($service->headerName('nonce'), 'nonce-a');
        $requestB->headers->set($service->headerName('timestamp'), '1717488000');
        $requestB->headers->set($service->headerName('nonce'), 'nonce-a');

        $signA = $service->sign($requestA, 'sapi_test_key', 'test-secret');
        $signB = $service->sign($requestB, 'sapi_test_key', 'test-secret');

        $this->assertNotSame($signA, $signB);
    }

    public function test_build_canonical_string_sorts_query_parameters(): void
    {
        $service = SApiSignatureService::make();

        $canonical = $service->buildCanonicalString(
            method: 'GET',
            path: '/sapi/ping',
            query: ['b' => '2', 'a' => '1'],
            body: '',
            timestamp: '1717488000',
            nonce: 'abc123',
            appKey: 'sapi_test_key',
        );

        $this->assertStringContainsString("GET\n/sapi/ping\na=1&b=2", $canonical);
    }
}
