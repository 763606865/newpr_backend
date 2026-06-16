<?php

namespace Tests\Unit;

use App\Filament\Resources\Cms\Announcements\Schemas\AnnouncementForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnnouncementFormFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_format_files_for_upload_converts_file_metadata_to_paths(): void
    {
        $paths = AnnouncementForm::formatFilesForUpload([
            ['name' => '附件.pdf', 'url' => 'https://cdn.example.com/announcement/file.pdf'],
            'announcement/another.doc',
        ]);

        $this->assertSame(['announcement/file.pdf', 'announcement/another.doc'], $paths);
    }

    public function test_dehydrate_uploaded_files_converts_paths_to_file_metadata(): void
    {
        Storage::fake('oss');

        $files = AnnouncementForm::dehydrateUploadedFiles([
            'announcement/file.pdf',
            'announcement/report.doc',
        ]);

        $this->assertSame([
            [
                'name' => 'file.pdf',
                'url' => Storage::disk('oss')->url('announcement/file.pdf'),
            ],
            [
                'name' => 'report.doc',
                'url' => Storage::disk('oss')->url('announcement/report.doc'),
            ],
        ], $files);
    }
}
