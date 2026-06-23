<?php

namespace Tests\Unit;

use App\Filament\Resources\Rc\SchoolActivities\Schemas\SchoolActivityForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchoolActivityFormFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_format_files_for_upload_converts_file_metadata_to_paths(): void
    {
        $paths = SchoolActivityForm::formatFilesForUpload([
            ['name' => '附件.pdf', 'url' => 'https://cdn.example.com/school-activity/files/file.pdf'],
            'school-activity/files/another.doc',
        ]);

        $this->assertSame(['school-activity/files/file.pdf', 'school-activity/files/another.doc'], $paths);
    }

    public function test_dehydrate_uploaded_files_converts_paths_to_file_metadata(): void
    {
        Storage::fake('oss');

        $files = SchoolActivityForm::dehydrateUploadedFiles([
            'school-activity/files/file.pdf',
            'school-activity/files/report.doc',
        ]);

        $this->assertSame([
            [
                'name' => 'file.pdf',
                'url' => Storage::disk('oss')->url('school-activity/files/file.pdf'),
            ],
            [
                'name' => 'report.doc',
                'url' => Storage::disk('oss')->url('school-activity/files/report.doc'),
            ],
        ], $files);
    }
}
