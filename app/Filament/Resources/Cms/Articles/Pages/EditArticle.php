<?php

namespace App\Filament\Resources\Cms\Articles\Pages;

use App\Filament\Resources\Cms\Articles\ArticleResource;
use App\Filament\Resources\Cms\Articles\Concerns\InteractsWithArticleContentForm;
use App\Models\Area;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    use InteractsWithArticleContentForm;

    protected static string $resource = ArticleResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $hierarchy = Area::resolveAreaHierarchy($data['city_code'] ?? null);

        $data = array_merge($data, [
            'province_code' => $hierarchy['province_code'],
            'area_city_code' => $hierarchy['city_code'],
        ]);

        return $this->mergeArticleContentIntoFormData($data, $this->getRecord()->content);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingArticleContent = $this->extractArticleContentFromFormData($data);

        return $this->removeArticleContentFromFormData($data);
    }

    protected function afterSave(): void
    {
        $this->persistArticleContent();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
