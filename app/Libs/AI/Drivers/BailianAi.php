<?php

namespace App\Libs\AI\Drivers;

class BailianAi extends AbstractHttpAiDriver
{
    protected function provider(): string
    {
        return 'bailian';
    }
}
