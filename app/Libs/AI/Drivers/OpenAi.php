<?php

namespace App\Libs\AI\Drivers;

class OpenAi extends AbstractHttpAiDriver
{
    protected function provider(): string
    {
        return 'openai';
    }
}
