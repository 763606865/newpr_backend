<?php

namespace App\Libs\ThirdParty;

use Closure;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Psr7\Utils;
use Psr\Log\LoggerInterface;

class LoggerMiddleware
{
    public function __construct(private LoggerInterface $logger) {}

    public function __invoke(callable $handler): Closure
    {
        return function ($request, $options) use ($handler) {
            return $handler($request, $options)->then(function ($response) use ($request) {
                $body = (string) $response->getBody();

                // 记录详细日志
                $formatter = new MessageFormatter(MessageFormatter::DEBUG);
                $this->logger->info($formatter->format($request, $response));

                // 创建新stream替换response body
                $newBody = Utils::streamFor($body);
                $response = $response->withBody($newBody);

                return $response;
            });
        };
    }
}
