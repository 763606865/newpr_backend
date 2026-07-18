<?php

namespace Tests\Unit\Services;

use App\Enums\RcIdentityType;
use App\Models\Rc\UserIdentity;
use App\Services\RcAiResumeService;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class RcAiResumeServiceTest extends TestCase
{
    public function test_job_seeker_can_parse_attachment_resume(): void
    {
        config()->set('ai.resume_parse_driver', 'custom');
        config()->set('ai.drivers.custom', [
            'base_url' => 'https://ai.example.com',
            'api_key' => 'custom-key',
            'model' => 'custom-model',
            'chat_path' => '/api/chat/completions',
            'resume_parse_path' => '/api/v1/parse',
            'timeout' => 30,
        ]);

        Http::fake([
            'ai.example.com/api/v1/parse' => Http::response([
                'code' => 200,
                'data' => [
                    'name' => '张三',
                    'skills' => ['PHP', 'Laravel'],
                ],
            ]),
        ]);

        $identity = new UserIdentity;
        $identity->identity_type = RcIdentityType::JobSeeker;

        $result = RcAiResumeService::make()->parseAttachmentResume($identity, 'https://files.example.com/resume.pdf');

        $this->assertSame('custom', $result['provider']);
        $this->assertSame('https://files.example.com/resume.pdf', $result['file_url']);
        $this->assertSame('张三', $result['parsed_resume']['name']);
        $this->assertSame(['PHP', 'Laravel'], $result['parsed_resume']['skills']);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://ai.example.com/api/v1/parse'
            && $request['file_url'] === 'https://files.example.com/resume.pdf');
    }

    public function test_only_job_seeker_can_parse_attachment_resume(): void
    {
        $identity = new UserIdentity;
        $identity->identity_type = RcIdentityType::Recruiter;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('请先切换为求职者身份。');

        RcAiResumeService::make()->parseAttachmentResume($identity, 'https://files.example.com/resume.pdf');
    }
}
