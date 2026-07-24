<?php

namespace App\Services;

use App\Libs\Facades\Jucai;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SmsService extends Service
{
    /**
     * @param  array<string, mixed>  $templateContent
     * @return array<string, mixed>
     *
     * @throws BindingResolutionException
     */
    public function send(
        string $mobile,
        string $templateId,
        array $templateContent = [],
        ?string $signature = null,
    ): array {
        $driver = config('sms.driver', 'jucai');

        if ($driver !== 'jucai') {
            throw new InvalidArgumentException(sprintf('不支持的短信服务商: %s', $driver));
        }

        return $this->sendViaJucai($mobile, $templateId, $templateContent, $signature);
    }

    /**
     * @param  array<string, mixed>  $templateContent
     * @return array<string, mixed>
     *
     * @throws BindingResolutionException
     */
    private function sendViaJucai(
        string $mobile,
        string $templateId,
        array $templateContent,
        ?string $signature,
    ): array {
        $response = Jucai::sms()->send(config('sms.jucai'), [
            'mobile' => trim($mobile),
            'signature' => $signature ?? '【中测高科人才测评】',
            'tpId' => $templateId,
            'tpContent' => $templateContent,
        ]);

        Log::info('sms_sent', [
            'driver' => 'jucai',
            'mobile' => $mobile,
            'template_id' => $templateId,
        ]);

        return $response;
    }
}
