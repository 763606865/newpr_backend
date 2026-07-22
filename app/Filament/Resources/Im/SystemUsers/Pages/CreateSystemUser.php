<?php

namespace App\Filament\Resources\Im\SystemUsers\Pages;

use App\Filament\Resources\Im\SystemUsers\SystemUserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSystemUser extends CreateRecord
{
    protected static string $resource = SystemUserResource::class;
}
