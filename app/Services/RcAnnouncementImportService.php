<?php

namespace App\Services;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsPublishStatus;
use App\Enums\RcAnnouncementApplyDeadlineType;
use App\Enums\RcEducationLevel;
use App\Enums\RcJobEmploymentType;
use App\Models\Rc\Announcement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use RuntimeException;
use ZipArchive;

class RcAnnouncementImportService extends Service
{
    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return [
            '公告标题',
            '副标题',
            '发布人名称',
            '发布人类型',
            '官网外链',
            '工作类型',
            '面向届别',
            '学历要求',
            '专业要求说明',
            '全国招聘',
            '工作城市编码',
            '专业编码',
            '报名开始时间',
            '截止类型',
            '报名截止时间',
            '摘要',
            '正文',
            '来源名称',
            '来源地址',
            '发布时间',
            '失效时间',
        ];
    }

    /**
     * @return list<list<string>>
     */
    public function templateRows(): array
    {
        return [[
            '示例招聘公告',
            '2026届校园招聘',
            '中测高科',
            '民营企业',
            'https://example.com/jobs',
            '社招全职、应届校园招聘',
            '2026、2027',
            '本科',
            '计算机、软件工程相关专业',
            '否',
            '360100、440300',
            '',
            '2026-08-01 09:00:00',
            '指定截止日期',
            '2026-09-30 18:00:00',
            '公告摘要',
            '公告正文，支持纯文本导入。',
            '官网',
            'https://example.com',
            '2026-08-01 09:00:00',
            '',
        ]];
    }

    public function writeTemplate(string $path): void
    {
        $writer = new XlsxWriter;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($this->headers()));

        foreach ($this->templateRows() as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        $this->addTemplateSelects($path);
    }

    /**
     * @return array{created: int, errors: list<string>}
     */
    public function importCsv(string $path): array
    {
        return $this->importReader($path, new CsvReader);
    }

    /**
     * @return array{created: int, errors: list<string>}
     */
    public function importXlsx(string $path): array
    {
        return $this->importReader($path, new XlsxReader);
    }

    /**
     * @return array{created: int, errors: list<string>}
     */
    public function import(string $path): array
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'csv' => $this->importCsv($path),
            'xlsx' => $this->importXlsx($path),
            default => throw new InvalidArgumentException('仅支持 CSV 或 XLSX 导入文件。'),
        };
    }

    /**
     * @return array{created: int, errors: list<string>}
     */
    private function importReader(string $path, ReaderInterface $reader): array
    {
        if (! is_readable($path)) {
            throw new InvalidArgumentException('导入文件不可读取。');
        }

        $created = 0;
        $errors = [];
        $reader->open($path);

        try {
            $sheet = $reader->getSheetIterator()->current();

            if ($sheet === null) {
                throw new InvalidArgumentException('导入文件没有工作表。');
            }

            $rows = $sheet->getRowIterator();
            $headers = [];
            $rowNumber = 0;

            DB::transaction(function () use ($rows, &$headers, &$created, &$errors, &$rowNumber): void {
                foreach ($rows as $spreadsheetRow) {
                    $rowNumber++;
                    $row = $this->normalizeRow(array_map(
                        static fn ($cell): mixed => $cell->getValue(),
                        $spreadsheetRow->getCells(),
                    ));

                    if ($rowNumber === 1) {
                        $headers = array_flip($row);

                        continue;
                    }

                    if ($this->isBlankRow($row)) {
                        continue;
                    }

                    try {
                        $this->createAnnouncementFromRow($headers, $row);
                        $created++;
                    } catch (InvalidArgumentException $exception) {
                        $errors[] = "第 {$rowNumber} 行：".$exception->getMessage();
                    }
                }

                if ($errors !== []) {
                    throw new InvalidArgumentException(implode("\n", $errors));
                }
            });
        } finally {
            $reader->close();
        }

        return [
            'created' => $created,
            'errors' => $errors,
        ];
    }

    public function writeCsv($handle, array $rows): void
    {
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $this->headers());

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
    }

    /**
     * @param  array<string, int>  $headerIndexes
     * @param  list<string>  $row
     */
    private function createAnnouncementFromRow(array $headerIndexes, array $row): Announcement
    {
        $title = $this->requiredString($headerIndexes, $row, '公告标题');
        $isNationwide = $this->booleanValue($this->value($headerIndexes, $row, '全国招聘'));
        $deadlineType = $this->enumValue(
            $this->value($headerIndexes, $row, '截止类型') ?: RcAnnouncementApplyDeadlineType::Fixed->value,
            RcAnnouncementApplyDeadlineType::class,
            '截止类型',
        );

        $announcement = Announcement::query()->create([
            'title' => $title,
            'sub_title' => $this->nullableString($headerIndexes, $row, '副标题'),
            'publisher_name' => $this->requiredString($headerIndexes, $row, '发布人名称'),
            'publisher_type' => $this->enumValue($this->value($headerIndexes, $row, '发布人类型'), CmsAnnouncementPublisherType::class, '发布人类型'),
            'link_url' => $this->requiredString($headerIndexes, $row, '官网外链'),
            'employment_types' => $this->enumList($this->value($headerIndexes, $row, '工作类型'), RcJobEmploymentType::class, '工作类型'),
            'graduation_years' => $this->integerList($this->value($headerIndexes, $row, '面向届别')),
            'education_level' => $this->nullableEnumValue($this->value($headerIndexes, $row, '学历要求'), RcEducationLevel::class, '学历要求'),
            'major_requirement' => $this->nullableString($headerIndexes, $row, '专业要求说明'),
            'is_nationwide' => $isNationwide,
            'apply_start_at' => $this->nullableString($headerIndexes, $row, '报名开始时间'),
            'apply_deadline_type' => $deadlineType,
            'apply_end_at' => $deadlineType === RcAnnouncementApplyDeadlineType::UntilFilled->value
                ? null
                : $this->nullableString($headerIndexes, $row, '报名截止时间'),
            'summary' => $this->nullableString($headerIndexes, $row, '摘要'),
            'content' => $this->nullableString($headerIndexes, $row, '正文'),
            'source_name' => $this->nullableString($headerIndexes, $row, '来源名称'),
            'source_url' => $this->nullableString($headerIndexes, $row, '来源地址'),
            'published_at' => $this->nullableString($headerIndexes, $row, '发布时间'),
            'expired_at' => $this->nullableString($headerIndexes, $row, '失效时间'),
            'is_top' => false,
            'status' => CmsPublishStatus::Published->value,
            'sort' => 99,
        ]);

        $announcement->syncCityCodes($isNationwide ? [] : $this->stringList($this->value($headerIndexes, $row, '工作城市编码')));
        $announcement->syncMajorCodes($this->stringList($this->value($headerIndexes, $row, '专业编码')));

        return $announcement;
    }

    /**
     * @param  list<string>  $row
     */
    private function value(array $headerIndexes, array $row, string $header): string
    {
        $index = $headerIndexes[$header] ?? null;

        if ($index === null) {
            return '';
        }

        return trim($row[$index] ?? '');
    }

    private function requiredString(array $headerIndexes, array $row, string $header): string
    {
        $value = $this->value($headerIndexes, $row, $header);

        if ($value === '') {
            throw new InvalidArgumentException($header.'不能为空。');
        }

        return $value;
    }

    private function nullableString(array $headerIndexes, array $row, string $header): ?string
    {
        $value = $this->value($headerIndexes, $row, $header);

        return $value === '' ? null : $value;
    }

    /**
     * @param  class-string<\BackedEnum>  $enumClass
     */
    private function nullableEnumValue(string $value, string $enumClass, string $label): ?int
    {
        if ($value === '') {
            return null;
        }

        return $this->enumValue($value, $enumClass, $label);
    }

    /**
     * @param  class-string<\BackedEnum>  $enumClass
     */
    private function enumValue(string $value, string $enumClass, string $label): int
    {
        foreach ($enumClass::cases() as $case) {
            if ((string) $case->value === $value || (method_exists($case, 'getLabel') && $case->getLabel() === $value)) {
                return (int) $case->value;
            }
        }

        throw new InvalidArgumentException($label.'无效：'.$value);
    }

    /**
     * @param  class-string<\BackedEnum>  $enumClass
     * @return list<int>
     */
    private function enumList(string $value, string $enumClass, string $label): array
    {
        return array_map(
            fn (string $item): int => $this->enumValue($item, $enumClass, $label),
            $this->stringList($value),
        );
    }

    /**
     * @return list<int>
     */
    private function integerList(string $value): array
    {
        return array_map(
            static fn (string $item): int => (int) preg_replace('/\D/', '', $item),
            $this->stringList($value),
        );
    }

    /**
     * @return list<string>
     */
    private function stringList(string $value): array
    {
        return array_values(array_filter(
            preg_split('/[、,，|;\s]+/u', $value) ?: [],
            static fn (string $item): bool => trim($item) !== '',
        ));
    }

    private function booleanValue(string $value): bool
    {
        return in_array(mb_strtolower($value), ['1', 'true', 'yes', 'y', '是', '全国', '置顶'], true);
    }

    /**
     * @param  list<string>|false  $row
     * @return list<string>
     */
    private function normalizeRow(array|false $row): array
    {
        if ($row === false) {
            return [];
        }

        return array_map(
            static fn (mixed $value): string => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $value)),
            $row,
        );
    }

    /**
     * @param  list<string>  $row
     */
    private function isBlankRow(array $row): bool
    {
        return collect($row)->every(static fn (mixed $value): bool => blank($value));
    }

    private function addTemplateSelects(string $path): void
    {
        $archive = new ZipArchive;

        if ($archive->open($path) !== true) {
            throw new RuntimeException('无法打开招聘公告导入模板。');
        }

        $worksheetPath = 'xl/worksheets/sheet1.xml';
        $worksheet = $archive->getFromName($worksheetPath);

        if ($worksheet === false) {
            $archive->close();

            throw new RuntimeException('招聘公告导入模板缺少工作表。');
        }

        $validations = [
            'D2:D11' => $this->enumLabels(CmsAnnouncementPublisherType::class),
            'F2:F11' => $this->enumLabels(RcJobEmploymentType::class),
            'G2:G11' => array_map(
                static fn (int $year): string => $year.'届',
                range((int) now()->format('Y') - 1, (int) now()->format('Y') + 3),
            ),
            'H2:H11' => $this->enumLabels(RcEducationLevel::class),
        ];

        $document = new \DOMDocument;
        $document->loadXML($worksheet);
        $worksheetElement = $document->documentElement;

        if ($worksheetElement === null) {
            $archive->close();

            throw new RuntimeException('招聘公告导入模板工作表无效。');
        }

        $dataValidations = $document->createElementNS($worksheetElement->namespaceURI, 'dataValidations');
        $dataValidations->setAttribute('count', (string) count($validations));

        foreach ($validations as $range => $options) {
            $dataValidation = $document->createElementNS($worksheetElement->namespaceURI, 'dataValidation');
            $dataValidation->setAttribute('type', 'list');
            $dataValidation->setAttribute('allowBlank', '1');
            $dataValidation->setAttribute('showErrorMessage', '1');
            $dataValidation->setAttribute('sqref', $range);
            $formula = '"'.implode(',', array_map(
                static fn (string $option): string => str_replace('"', '""', $option),
                $options,
            )).'"';
            $dataValidation->appendChild(
                $document->createElementNS($worksheetElement->namespaceURI, 'formula1', $formula),
            );
            $dataValidations->appendChild($dataValidation);
        }

        $insertBefore = null;

        foreach ($worksheetElement->childNodes as $childNode) {
            if (in_array($childNode->localName, [
                'hyperlinks',
                'printOptions',
                'pageMargins',
                'pageSetup',
                'headerFooter',
                'rowBreaks',
                'colBreaks',
                'customProperties',
                'cellWatches',
                'ignoredErrors',
                'smartTags',
                'drawing',
                'legacyDrawing',
                'legacyDrawingHF',
                'picture',
                'oleObjects',
                'controls',
                'webPublishItems',
                'tableParts',
                'extLst',
            ], true)) {
                $insertBefore = $childNode;

                break;
            }
        }

        $worksheetElement->insertBefore($dataValidations, $insertBefore);
        $archive->deleteName($worksheetPath);
        $archive->addFromString($worksheetPath, $document->saveXML());
        $archive->close();
    }

    /**
     * @param  class-string<\BackedEnum>  $enumClass
     * @return list<string>
     */
    private function enumLabels(string $enumClass): array
    {
        return array_map(
            static fn (\BackedEnum $case): string => method_exists($case, 'getLabel')
                ? (string) $case->getLabel()
                : (string) $case->value,
            $enumClass::cases(),
        );
    }
}
