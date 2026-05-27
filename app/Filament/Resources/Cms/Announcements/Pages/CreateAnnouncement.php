<?php

namespace App\Filament\Resources\Cms\Announcements\Pages;

use App\Filament\Resources\Cms\Announcements\AnnouncementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;
}
