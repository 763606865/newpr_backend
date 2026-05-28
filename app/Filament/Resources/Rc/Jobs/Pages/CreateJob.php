<?php

namespace App\Filament\Resources\Rc\Jobs\Pages;

use App\Filament\Resources\Rc\Jobs\JobResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJob extends CreateRecord
{
    protected static string $resource = JobResource::class;
}
