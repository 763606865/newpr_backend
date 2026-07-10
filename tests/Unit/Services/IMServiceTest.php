<?php

namespace Tests\Unit\Services;

use App\Models\Rc\UserIdentity;
use App\Models\User;
use App\Services\IMService;
use Tests\TestCase;

class IMServiceTest extends TestCase
{
    public function test_createOrUpdate(): void
    {
        $identity = UserIdentity::firstOrFail();

        IMService::make()->createOrUpdate($identity);
    }
}
