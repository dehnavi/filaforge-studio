<?php
namespace FilaForge\Studio\Documents;
use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Models\PageLocale;
use FilaForge\Studio\Models\Revision;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
final class PageDocuments {
    public function __construct(private DocumentValidator $validator) {}
    public function save(PageLocale $page, array $document, int $expected): int {
        Gate::authorize('update', $page);
        $this->validator->validate($document);
        app(\FilaForge\Studio\Media\MediaReferences::class)->validate($document);
        if ($document['locale'] !== $page->locale || $document['direction'] !== $page->direction) throw new \InvalidArgumentException('Document locale mismatch.');
        $fresh=PageLocale::findOrFail($page->id);
        if($fresh->lock_version!==$expected)throw new \RuntimeException('Document conflict. Reload before saving.');
        app(EditingRules::class)->authorize($fresh,$fresh->draft_document,$document);
        app(\FilaForge\Studio\Integrations\PageIntegrations::class)->authorizeChange($fresh,$fresh->draft_document,$document);
        $affected = PageLocale::query()->whereKey($page->id)->where('lock_version',$expected)->update(['draft_document'=>json_encode($document, JSON_THROW_ON_ERROR),'lock_version'=>$expected+1,'updated_at'=>now()]);
        if (!$affected) throw new \RuntimeException('Document conflict. Reload before saving.');
        return $expected+1;
    }
    public function publish(PageLocale $page, int $expected): Revision {
        Gate::authorize('publish', $page);
        return DB::transaction(function () use ($page,$expected) {
            $fresh = PageLocale::query()->lockForUpdate()->findOrFail($page->id);
            if ($fresh->lock_version !== $expected) throw new \RuntimeException('Document conflict.');
            $this->validator->validate($fresh->draft_document);
            app(\FilaForge\Studio\Media\MediaReferences::class)->validate($fresh->draft_document);
            app(\FilaForge\Studio\Integrations\PageIntegrations::class)->authorizePublication($fresh,$fresh->draft_document);
            $revision = $fresh->revisions()->create(['document'=>$fresh->draft_document,'version'=>$expected]);
            $fresh->forceFill(['published_revision_id'=>$revision->id,'lock_version'=>$expected+1])->save();
            return $revision;
        });
    }
}
