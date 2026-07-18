<?php

namespace App\Libs\AI;

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

                $formatter = new MessageFormatter(MessageFormatter::DEBUG);
                $this->logger->info($formatter->format($request, $response));

                return $response->withBody(Utils::streamFor($body));
            });
        };
    }
}
