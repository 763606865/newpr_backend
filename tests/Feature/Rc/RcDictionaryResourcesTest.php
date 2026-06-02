<?php

namespace Tests\Feature\Rc;

use App\Filament\Resources\Rc\Industries\IndustryResource;
use App\Filament\Resources\Rc\Positions\PositionResource;
use App\Models\Rc\Industry;
use App\Models\Rc\Position;
use Filament\Support\Icons\Heroicon;
use ReflectionClass;
use Tests\TestCase;

class RcDictionaryResourcesTest extends TestCase
{
    public function test_rc_dictionary_resources_have_index_create_and_edit_pages(): void
    {
        foreach ($this->resourceClasses() as $resourceClass => $expectedModelClass) {
            /** @var class-string $resourceClass */
            $pages = $resourceClass::getPages();

            $this->assertArrayHasKey('index', $pages);
            $this->assertArrayHasKey('create', $pages);
            $this->assertArrayHasKey('edit', $pages);
            $this->assertSame($expectedModelClass, $resourceClass::getModel());
        }
    }

    public function test_rc_dictionary_resources_use_expected_navigation_configuration(): void
    {
        foreach (array_keys($this->resourceClasses()) as $resourceClass) {
            $reflection = new ReflectionClass($resourceClass);

            $navigationGroup = $reflection->getProperty('navigationGroup')->getValue();
            $navigationIcon = $reflection->getProperty('navigationIcon')->getValue();

            $this->assertSame('RC招聘', $navigationGroup);
            $this->assertSame(Heroicon::OutlinedRectangleStack, $navigationIcon);
        }

        $this->assertSame(10, (new ReflectionClass(IndustryResource::class))->getProperty('navigationSort')->getValue());
        $this->assertSame(20, (new ReflectionClass(PositionResource::class))->getProperty('navigationSort')->getValue());
    }

    /**
     * @return array<class-string, class-string>
     */
    private function resourceClasses(): array
    {
        return [
            IndustryResource::class => Industry::class,
            PositionResource::class => Position::class,
        ];
    }
}
