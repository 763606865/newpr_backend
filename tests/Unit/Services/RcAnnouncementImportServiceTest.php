<?php

namespace Tests\Unit\Services;

use App\Enums\CmsPublishStatus;
use App\Models\Rc\Announcement;
use App\Services\RcAnnouncementImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class RcAnnouncementImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_excludes_managed_columns_and_adds_selects_to_first_ten_rows(): void
    {
        $service = RcAnnouncementImportService::make();
        $path = tempnam(sys_get_temp_dir(), 'rc-announcement-template-test-');

        try {
            $service->writeTemplate($path);

            $this->assertNotContains('是否置顶', $service->headers());
            $this->assertNotContains('状态', $service->headers());
            $this->assertNotContains('排序', $service->headers());

            $archive = new ZipArchive;
            $this->assertTrue($archive->open($path));
            $worksheet = $archive->getFromName('xl/worksheets/sheet1.xml');
            $archive->close();

            $this->assertIsString($worksheet);
            $this->assertStringContainsString('sqref="D2:D11"', $worksheet);
            $this->assertStringContainsString('sqref="F2:F11"', $worksheet);
            $this->assertStringContainsString('sqref="G2:G11"', $worksheet);
            $this->assertStringContainsString('sqref="H2:H11"', $worksheet);
            $this->assertStringContainsString('民营企业', $worksheet);
            $this->assertStringContainsString('应届校园招聘', $worksheet);
            $this->assertStringContainsString('本科', $worksheet);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_import_uses_managed_defaults_when_columns_are_absent(): void
    {
        $service = RcAnnouncementImportService::make();
        $path = tempnam(sys_get_temp_dir(), 'rc-announcement-import-test-');
        $service->writeTemplate($path);

        try {
            $result = $service->importXlsx($path);
            $announcement = Announcement::query()->sole();

            $this->assertSame(1, $result['created']);
            $this->assertFalse($announcement->is_top);
            $this->assertSame(CmsPublishStatus::Published, $announcement->status);
            $this->assertSame(99, $announcement->sort);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
