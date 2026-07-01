<?php

namespace Tests\Unit\Libs\Ocr;

use AlibabaCloud\SDK\Ocrapi\V20210707\Models\RecognizeBusinessLicenseRequest;
use AlibabaCloud\SDK\Ocrapi\V20210707\Models\RecognizeBusinessLicenseResponse;
use AlibabaCloud\SDK\Ocrapi\V20210707\Models\RecognizeBusinessLicenseResponseBody;
use AlibabaCloud\SDK\Ocrapi\V20210707\Models\RecognizeGeneralRequest;
use AlibabaCloud\SDK\Ocrapi\V20210707\Models\RecognizeGeneralResponse;
use AlibabaCloud\SDK\Ocrapi\V20210707\Models\RecognizeGeneralResponseBody;
use AlibabaCloud\SDK\Ocrapi\V20210707\Ocrapi;
use App\Libs\Facades\Ocr;
use App\Libs\Ocr\Data\BusinessLicenseResult;
use App\Libs\Ocr\Data\RecognizeResult;
use App\Libs\Ocr\Drivers\AliyunOcr;
use App\Libs\Ocr\OcrException;
use Darabonba\OpenApi\Exceptions\AlibabaCloudException;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class AliyunOcrTest extends TestCase
{
    /** @var array<int, array{level: string, message: string, context: array<string, mixed>}> */
    protected array $logRecords = [];

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_ocr_recognize_business_license()
    {
        $content = file_get_contents('/Users/zn/Downloads/a9a22b117c4702e2c388436a9e160e20.jpeg');
        $result = Ocr::recognizeBusinessLicenseByUrl('https://newpr-develop.oss-cn-hangzhou.aliyuncs.com/uploads/rc/file/2026/06/10/qCU3tRAXlfz5UDuWCUUa.jpg');
        dd($result);
    }

    public function test_recognize_general_by_url_returns_parsed_result(): void
    {
        $logger = $this->createRecordingLogger();
        $client = Mockery::mock(Ocrapi::class);
        $client->shouldReceive('recognizeGeneral')
            ->once()
            ->with(Mockery::type(RecognizeGeneralRequest::class))
            ->andReturn($this->successResponse());

        $result = $this->driver($client, $logger)->recognizeGeneralByUrl('https://example.com/image.png?token=secret');

        $this->assertSame('Hello OCR', $result->content);
        $this->assertSame('req-123', $result->requestId);
        $this->assertCount(1, $result->words);
        $this->assertSame('Hello OCR', $result->words[0]->word);
        $this->assertSame(99, $result->words[0]->probability);
        $this->assertSame(800, $result->width);
        $this->assertSame(600, $result->height);

        $this->assertLogContains('info', 'OCR 请求开始', [
            'action' => 'RecognizeGeneral',
            'source' => 'url',
            'url' => 'https://example.com/image.png',
        ]);
        $this->assertLogContains('info', 'OCR 请求成功', [
            'request_id' => 'req-123',
            'content_length' => 9,
            'word_count' => 1,
        ]);
    }

    public function test_recognize_general_by_content_uses_binary_body(): void
    {
        $client = Mockery::mock(Ocrapi::class);
        $client->shouldReceive('recognizeGeneral')
            ->once()
            ->with(Mockery::on(function (RecognizeGeneralRequest $request): bool {
                return $request->body !== null;
            }))
            ->andReturn($this->successResponse());

        $result = $this->driver($client)->recognizeGeneralByContent('fake-image-binary');

        $this->assertInstanceOf(RecognizeResult::class, $result);
        $this->assertSame('Hello OCR', $result->content);
    }

    public function test_recognize_business_license_by_url_returns_parsed_result(): void
    {
        $logger = $this->createRecordingLogger();
        $client = Mockery::mock(Ocrapi::class);
        $client->shouldReceive('recognizeBusinessLicense')
            ->once()
            ->with(Mockery::type(RecognizeBusinessLicenseRequest::class))
            ->andReturn($this->businessLicenseSuccessResponse());

        $result = $this->driver($client, $logger)->recognizeBusinessLicenseByUrl('https://example.com/license.png');

        $this->assertInstanceOf(BusinessLicenseResult::class, $result);
        $this->assertSame('req-bl-123', $result->requestId);
        $this->assertSame('91310000MA1FL2XX1X', $result->creditCode);
        $this->assertSame('示例科技有限公司', $result->companyName);
        $this->assertSame('有限责任公司', $result->companyType);
        $this->assertSame('张三', $result->legalPerson);
        $this->assertSame('2020年01月01日', $result->registrationDate);

        $this->assertLogContains('info', 'OCR 请求开始', [
            'action' => 'RecognizeBusinessLicense',
            'source' => 'url',
        ]);
        $this->assertLogContains('info', 'OCR 请求成功', [
            'request_id' => 'req-bl-123',
            'company_name' => '示例科技有限公司',
            'credit_code' => '91310000MA1FL2XX1X',
        ]);
    }

    public function test_recognize_business_license_by_content_uses_binary_body(): void
    {
        $client = Mockery::mock(Ocrapi::class);
        $client->shouldReceive('recognizeBusinessLicense')
            ->once()
            ->with(Mockery::on(function (RecognizeBusinessLicenseRequest $request): bool {
                return $request->body !== null;
            }))
            ->andReturn($this->businessLicenseSuccessResponse());

        $result = $this->driver($client)->recognizeBusinessLicenseByContent('fake-license-binary');

        $this->assertSame('91310000MA1FL2XX1X', $result->creditCode);
    }

    public function test_recognize_general_by_url_throws_when_url_is_blank(): void
    {
        $this->expectException(OcrException::class);
        $this->expectExceptionMessage('图片 URL 不能为空');

        $this->driver()->recognizeGeneralByUrl('   ');
    }

    public function test_recognize_business_license_by_url_throws_when_url_is_blank(): void
    {
        $this->expectException(OcrException::class);
        $this->expectExceptionMessage('图片 URL 不能为空');

        $this->driver()->recognizeBusinessLicenseByUrl('   ');
    }

    public function test_client_creation_throws_when_access_key_is_missing(): void
    {
        $this->expectException(OcrException::class);
        $this->expectExceptionMessage('未配置阿里云 OCR AccessKey');

        (new AliyunOcr([
            'access_key_id' => '',
            'access_key_secret' => '',
        ]))->recognizeGeneralByUrl('https://example.com/image.png');
    }

    public function test_timeout_config_seconds_are_converted_to_sdk_milliseconds(): void
    {
        $driver = new AliyunOcr([
            'access_key_id' => 'test-key-id',
            'access_key_secret' => 'test-key-secret',
            'connect_timeout' => 5,
            'read_timeout' => 10,
        ]);

        $method = new \ReflectionMethod($driver, 'timeoutMilliseconds');

        $this->assertSame(5000, $method->invoke($driver, 'connect_timeout', 5));
        $this->assertSame(10000, $method->invoke($driver, 'read_timeout', 10));
    }

    public function test_parse_response_throws_when_api_returns_error_code(): void
    {
        $logger = $this->createRecordingLogger();

        $client = Mockery::mock(Ocrapi::class);
        $client->shouldReceive('recognizeGeneral')
            ->once()
            ->andReturn($this->errorResponse('InvalidImage', '图片无效'));

        try {
            $this->driver($client, $logger)->recognizeGeneralByUrl('https://example.com/image.png');
            $this->fail('应抛出 OcrException');
        } catch (OcrException $exception) {
            $this->assertSame('图片无效', $exception->getMessage());
        }

        $this->assertLogContains('error', 'OCR 请求失败', [
            'request_id' => 'req-error',
            'code' => 'InvalidImage',
            'message' => '图片无效',
        ]);
    }

    public function test_logs_request_id_when_sdk_throws_exception(): void
    {
        $logger = $this->createRecordingLogger();

        $client = Mockery::mock(Ocrapi::class);
        $client->shouldReceive('recognizeGeneral')
            ->once()
            ->andThrow(new AlibabaCloudException([
                'statusCode' => 400,
                'code' => 'InvalidParameter',
                'message' => '参数无效',
                'description' => '参数无效',
                'requestId' => 'req-sdk-error',
            ]));

        try {
            $this->driver($client, $logger)->recognizeGeneralByUrl('https://example.com/image.png');
            $this->fail('应抛出 OcrException');
        } catch (OcrException $exception) {
            $this->assertStringContainsString('阿里云 OCR 请求失败', $exception->getMessage());
        }

        $this->assertLogContains('error', 'OCR 请求失败', [
            'request_id' => 'req-sdk-error',
            'code' => 'InvalidParameter',
        ]);
    }

    private function driver(?Ocrapi $client = null, ?LoggerInterface $logger = null): AliyunOcr
    {
        return new AliyunOcr([
            'access_key_id' => 'test-key-id',
            'access_key_secret' => 'test-key-secret',
            'endpoint' => 'ocr-api.cn-hangzhou.aliyuncs.com',
            'region_id' => 'cn-hangzhou',
        ], $client, $logger);
    }

    private function createRecordingLogger(): LoggerInterface
    {
        $this->logRecords = [];

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('emergency', 'alert', 'critical', 'warning', 'notice', 'debug')->zeroOrMoreTimes();
        $logger->shouldReceive('info')->andReturnUsing(function (string $message, array $context = []) {
            $this->logRecords[] = ['level' => 'info', 'message' => $message, 'context' => $context];
        });
        $logger->shouldReceive('error')->andReturnUsing(function (string $message, array $context = []) {
            $this->logRecords[] = ['level' => 'error', 'message' => $message, 'context' => $context];
        });

        return $logger;
    }

    /**
     * @param  array<string, mixed>  $expectedContext
     */
    private function assertLogContains(string $level, string $message, array $expectedContext = []): void
    {
        foreach ($this->logRecords as $record) {
            if ($record['level'] !== $level || $record['message'] !== $message) {
                continue;
            }

            foreach ($expectedContext as $key => $value) {
                if (($record['context'][$key] ?? null) !== $value) {
                    continue 2;
                }
            }

            $this->addToAssertionCount(1);

            return;
        }

        $this->fail(sprintf('未找到日志记录：[%s] %s', $level, $message));
    }

    private function successResponse(): RecognizeGeneralResponse
    {
        $body = new RecognizeGeneralResponseBody([
            'requestId' => 'req-123',
            'data' => json_encode([
                'content' => 'Hello OCR',
                'width' => 800,
                'height' => 600,
                'orgWidth' => 800,
                'orgHeight' => 600,
                'prism_wordsInfo' => [
                    [
                        'word' => 'Hello OCR',
                        'prob' => 99,
                        'x' => 10,
                        'y' => 20,
                        'width' => 100,
                        'height' => 30,
                        'angle' => 0,
                        'direction' => 0,
                        'pos' => [
                            ['x' => 10, 'y' => 20],
                            ['x' => 110, 'y' => 20],
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        return new RecognizeGeneralResponse([
            'statusCode' => 200,
            'body' => $body,
        ]);
    }

    private function businessLicenseSuccessResponse(): RecognizeBusinessLicenseResponse
    {
        $body = new RecognizeBusinessLicenseResponseBody([
            'requestId' => 'req-bl-123',
            'data' => json_encode([
                'data' => [
                    'creditCode' => '91310000MA1FL2XX1X',
                    'companyName' => '示例科技有限公司',
                    'companyType' => '有限责任公司',
                    'businessAddress' => '上海市浦东新区示例路 1 号',
                    'legalPerson' => '张三',
                    'businessScope' => '软件开发',
                    'registeredCapital' => '1000万元',
                    'RegistrationDate' => '2020年01月01日',
                    'validPeriod' => '2020年01月01日至长期',
                    'validFromDate' => '20200101',
                    'validToDate' => '29991231',
                    'companyForm' => '',
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        return new RecognizeBusinessLicenseResponse([
            'statusCode' => 200,
            'body' => $body,
        ]);
    }

    private function errorResponse(string $code, string $message): RecognizeGeneralResponse
    {
        $body = new RecognizeGeneralResponseBody([
            'code' => $code,
            'message' => $message,
            'requestId' => 'req-error',
        ]);

        return new RecognizeGeneralResponse([
            'statusCode' => 200,
            'body' => $body,
        ]);
    }
}
