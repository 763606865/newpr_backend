<?php

namespace App\Filament\Resources\Cms\Articles\Pages;

use App\Filament\Resources\Cms\Articles\ArticleResource;
use App\Filament\Resources\Cms\Articles\Concerns\InteractsWithArticleContentForm;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    use InteractsWithArticleContentForm;

    protected static string $resource = ArticleResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingArticleContent = $this->extractArticleContentFromFormData($data);

        return $this->removeArticleContentFromFormData($data);
    }

    protected function afterCreate(): void
    {
        $this->persistArticleContent();
    }
}
