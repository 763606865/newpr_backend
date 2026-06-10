<?php

namespace Tests\Feature\Rc;

use App\Libs\Ocr\Data\BusinessLicenseResult;
use App\Libs\Ocr\Ocr;
use App\Libs\Ocr\OcrException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class ToolControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_recognize_business_license_requires_authentication(): void
    {
        $response = $this->postJson('/rc/tools/ocr/business-license', [
            'url' => 'https://example.com/license.jpg',
        ]);

        $this->assertContains($response->status(), [401, 422, 500]);
    }

    public function test_recognize_business_license_by_url_returns_parsed_fields(): void
    {
        $user = User::factory()->create();

        $ocr = Mockery::mock(Ocr::class);
        $ocr->shouldReceive('recognizeBusinessLicenseByUrl')
            ->once()
            ->with('https://example.com/license.jpg')
            ->andReturn($this->sampleBusinessLicenseResult());
        $this->instance(Ocr::class, $ocr);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/tools/ocr/business-license', [
                'url' => 'https://example.com/license.jpg',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.credit_code', '91310000MA1FL2XX1X')
            ->assertJsonPath('data.company_name', '示例科技有限公司')
            ->assertJsonPath('data.legal_person', '张三')
            ->assertJsonPath('data.request_id', 'req-bl-123');
    }

    public function test_recognize_business_license_by_file_upload(): void
    {
        $user = User::factory()->create();

        $ocr = Mockery::mock(Ocr::class);
        $ocr->shouldReceive('recognizeBusinessLicenseByContent')
            ->once()
            ->andReturn($this->sampleBusinessLicenseResult());
        $this->instance(Ocr::class, $ocr);

        $response = $this
            ->actingAs($user, 'rc')
            ->post('/rc/tools/ocr/business-license', [
                'file' => UploadedFile::fake()->image('license.jpg'),
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.company_name', '示例科技有限公司');
    }

    public function test_recognize_business_license_rejects_file_and_url_together(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/tools/ocr/business-license', [
                'file' => UploadedFile::fake()->image('license.jpg'),
                'url' => 'https://example.com/license.jpg',
            ]);

        $response->assertStatus(422);
    }

    public function test_recognize_business_license_returns_error_when_ocr_fails(): void
    {
        $user = User::factory()->create();

        $ocr = Mockery::mock(Ocr::class);
        $ocr->shouldReceive('recognizeBusinessLicenseByUrl')
            ->once()
            ->with('https://example.com/invalid.jpg')
            ->andThrow(new OcrException('图片无效'));
        $this->instance(Ocr::class, $ocr);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/tools/ocr/business-license', [
                'url' => 'https://example.com/invalid.jpg',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '图片无效');
    }

    private function sampleBusinessLicenseResult(): BusinessLicenseResult
    {
        return new BusinessLicenseResult(
            creditCode: '91310000MA1FL2XX1X',
            companyName: '示例科技有限公司',
            companyType: '有限责任公司',
            businessAddress: '上海市浦东新区示例路 1 号',
            legalPerson: '张三',
            businessScope: '软件开发',
            registeredCapital: '1000万元',
            registrationDate: '2020年01月01日',
            validPeriod: '2020年01月01日至长期',
            validFromDate: '20200101',
            validToDate: '29991231',
            companyForm: '',
            requestId: 'req-bl-123',
            raw: [],
        );
    }
}
