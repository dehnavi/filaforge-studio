<?php
namespace FilaForge\Studio\Filament\Resources\Pages;
use FilaForge\Studio\Models\Page;
use FilaForge\Studio\Models\PageLocale;
use FilaForge\Studio\Core\DocumentValidator;
use Illuminate\Database\Eloquent\Model;
class CreatePage extends \Filament\Resources\Pages\CreateRecord {
    protected static string $resource = \FilaForge\Studio\Filament\Resources\PageResource::class;
    protected function handleRecordCreation(array $data): Model {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $data['page_id'] = Page::create()->id;
            $data['draft_document'] = DocumentValidator::empty($data['locale'],$data['direction']);
            return PageLocale::create($data);
        });
    }
}
