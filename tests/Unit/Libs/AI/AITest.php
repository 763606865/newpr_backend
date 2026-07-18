<?php

namespace Tests\Unit\Libs\AI;

use App\Libs\AI\AIManager;
use App\Libs\AI\Contracts\AiDriver;
use App\Libs\AI\Drivers\CustomAi;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AITest extends TestCase
{
    public function test_it_resolves_configured_drivers(): void
    {
        config()->set('ai.drivers.custom', [
            'base_url' => 'https://ai.example.com',
            'api_key' => 'custom-key',
            'model' => 'custom-model',
            'chat_path' => '/api/chat/completions',
        ]);

        $manager = new AIManager(app());

        $this->assertInstanceOf(AiDriver::class, $manager->driver('custom'));
    }

    public function test_it_sends_chat_request_and_normalizes_response(): void
    {
        config()->set('ai.drivers.openai', [
            'base_url' => 'https://api.openai.test/v1',
            'api_key' => 'openai-key',
            'model' => 'gpt-test',
            'chat_path' => '/chat/completions',
            'timeout' => 30,
        ]);

        Http::fake([
            'api.openai.test/v1/chat/completions' => Http::response([
                'model' => 'gpt-test',
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => '分析完成',
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 5,
                    'total_tokens' => 15,
                ],
            ]),
        ]);

        $result = (new AIManager(app()))->driver('openai')->chat([
            ['role' => 'user', 'content' => '分析这份简历'],
        ]);

        $this->assertSame('openai', $result['provider']);
        $this->assertSame('gpt-test', $result['model']);
        $this->assertSame('分析完成', $result['content']);
        $this->assertSame(15, $result['usage']['total_tokens']);

        Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer openai-key')
            && $request['model'] === 'gpt-test'
            && $request['messages'][0]['content'] === '分析这份简历');
    }

    public function test_custom_driver_parses_resume_by_file_url(): void
    {
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
                    'phone' => '13800138000',
                    'skills' => ['PHP', 'Laravel'],
                ],
            ]),
        ]);

        $result = (new AIManager(app()))->driver('custom')->parseResumeByFileUrl('https://files.example.com/resume.pdf');

        $this->assertSame('custom', $result['provider']);
        $this->assertSame('https://files.example.com/resume.pdf', $result['file_url']);
        $this->assertSame('张三', $result['data']['name']);
        $this->assertSame(['PHP', 'Laravel'], $result['data']['skills']);

        Http::assertSent(fn ($request): bool => $request->hasHeader('X-API-Key', 'custom-key')
            && $request->url() === 'https://ai.example.com/api/v1/parse'
            && $request['file_url'] === 'https://files.example.com/resume.pdf');
    }

    public function test_blank_timeout_falls_back_to_default_timeout(): void
    {
        $driver = new class(['timeout' => '']) extends CustomAi
        {
            public function timeoutValue(): int
            {
                return $this->timeout();
            }
        };

        $this->assertSame(30, $driver->timeoutValue());
    }
}
