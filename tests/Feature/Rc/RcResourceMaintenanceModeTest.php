<?php

namespace Tests\Feature\Rc;

use App\Filament\Resources\Rc\Interviews\InterviewResource;
use App\Filament\Resources\Rc\Offers\OfferResource;
use App\Filament\Resources\Rc\UserIdentities\UserIdentityResource;
use Filament\Support\Icons\Heroicon;
use ReflectionClass;
use Tests\TestCase;

class RcResourceMaintenanceModeTest extends TestCase
{
    public function test_rc_resources_only_register_index_and_edit_pages(): void
    {
        foreach (array_keys($this->resourceClasses()) as $resourceClass) {
            /** @var class-string $resourceClass */
            $pages = $resourceClass::getPages();

            $this->assertArrayHasKey('index', $pages);
            $this->assertArrayHasKey('edit', $pages);
            $this->assertArrayNotHasKey('create', $pages);
        }
    }

    public function test_create_page_classes_are_removed_for_maintenance_mode(): void
    {
        $this->assertFalse(class_exists('App\\Filament\\Resources\\Rc\\UserIdentities\\Pages\\CreateUserIdentity'));
        $this->assertFalse(class_exists('App\\Filament\\Resources\\Rc\\Interviews\\Pages\\CreateInterview'));
        $this->assertFalse(class_exists('App\\Filament\\Resources\\Rc\\Offers\\Pages\\CreateOffer'));
    }

    public function test_rc_resources_use_unified_navigation_group_icon_and_sort(): void
    {
        foreach ($this->resourceClasses() as $resourceClass => $expectedSort) {
            $reflection = new ReflectionClass($resourceClass);

            $navigationGroup = $reflection->getProperty('navigationGroup')->getValue();
            $navigationIcon = $reflection->getProperty('navigationIcon')->getValue();
            $navigationSort = $reflection->getProperty('navigationSort')->getValue();

            $this->assertSame('RC招聘', $navigationGroup);
            $this->assertSame(Heroicon::OutlinedRectangleStack, $navigationIcon);
            $this->assertSame($expectedSort, $navigationSort);
        }
    }

    /**
     * @return array<class-string, int>
     */
    private function resourceClasses(): array
    {
        return [
            UserIdentityResource::class => 40,
            InterviewResource::class => 50,
            OfferResource::class => 60,
        ];
    }
}
