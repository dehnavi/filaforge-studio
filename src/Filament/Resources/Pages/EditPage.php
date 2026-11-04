<?php
namespace FilaForge\Studio\Filament\Resources\Pages;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use FilaForge\Studio\Models\PageLocale;
use FilaForge\Studio\Documents\PageDocuments;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
final class EditPage extends \Filament\Resources\Pages\Page {
    protected static string $resource = \FilaForge\Studio\Filament\Resources\PageResource::class;
    protected string $view = 'filaforge::editor.page';
    #[Locked] public string $pageId;
    #[Locked] public string $customWidgetType = '';
    public array $customSettings = [];
    public array $settings = ['text'=>'','tag'=>'h2'];
    public function mount(string $record): void { $page=PageLocale::findOrFail($record); Gate::authorize('update',$page); $this->pageId=$page->id; }
    public function form(Schema $schema): Schema {
        return $schema->components([TextInput::make('text')->label(__('filaforge::studio.text'))->required()->maxLength(10000),Select::make('tag')->label(__('filaforge::studio.tag'))->options(array_combine(['h1','h2','h3','h4','h5','h6'],['H1','H2','H3','H4','H5','H6']))->required()])->statePath('settings');
    }
    public function initialState(): array { $p=$this->page(); return ['document'=>$p->draft_document,'version'=>$p->lock_version,'clipboardKey'=>'filaforge:clipboard:'.hash('sha256',request()->getHost().'|'.auth()->id()),'recoveryKey'=>'filaforge:'.hash('sha256',request()->getHost().'|'.auth()->id().'|'.$p->id),'autosave'=>(bool)config('filaforge.autosave',true),'autosaveDelay'=>max(500,(int)config('filaforge.autosave_delay',1500))]; }
    private function page(): PageLocale { $p=PageLocale::findOrFail($this->pageId); Gate::authorize('update',$p); return $p; }
    public function saveDocument(array $document,int $version): array {
        try { return ['version'=>app(PageDocuments::class)->save($this->page(),$document,$version)]; }
        catch (\InvalidArgumentException|\RuntimeException $e) { return ['error'=>$e->getMessage()]; }
    }
    public function publishDocument(int $version): array {
        try { app(PageDocuments::class)->publish($this->page(),$version); return ['version'=>$version+1]; }
        catch (\InvalidArgumentException|\RuntimeException $e) { return ['error'=>$e->getMessage()]; }
    }
    public function validatedSettings(): array { $this->page(); return $this->form->getState(); }
    public function importHtml(string $html): array {
        $page = $this->page();
        try {
            $result = app(\FilaForge\Studio\Importing\HtmlImporter::class)->import($html, $page->draft_document['locale'], $page->draft_document['direction']);
            $result['warnings'] = array_map(fn ($key) => __('filaforge::studio.'.$key), $result['warnings']);
            return $result;
        } catch (\InvalidArgumentException $e) {
            $key = in_array($e->getMessage(), ['import_invalid','import_empty','import_limits'], true) ? $e->getMessage() : 'import_invalid';
            return ['error'=>__('filaforge::studio.'.$key)];
        }
    }
    public function dynamicCatalog(): array
    {
        $page = $this->page();
        $widgets = collect(app(\FilaForge\Studio\Core\WidgetRegistry::class)->all());
        return [
            'scriptPermissions' => [
                'manage' => Gate::allows('filaforge.manageScripts', $page),
                'gtm' => (bool) config('filaforge.integrations.gtm_enabled', true),
                'custom' => (bool) config('filaforge.integrations.custom_scripts_enabled', false),
            ],
            'fonts' => app(\FilaForge\Studio\Fonts\FontRegistry::class)->catalog(app()->getLocale()),
            'fontDefault' => app(\FilaForge\Studio\Fonts\FontRegistry::class)->defaultFor($page->locale),
            'sources' => app(\FilaForge\Studio\DynamicData\DataRegistry::class)->catalog(),
            'formatters' => app(\FilaForge\Studio\DynamicData\Formatters::class)->names(),
            'components' => app(\FilaForge\Studio\DynamicData\ShortcodeRegistry::class)->catalog(),
            'structureManager' => Gate::allows('filaforge.manageStructure', $page),
            'tokens' => app(\FilaForge\Studio\Rendering\DesignTokens::class)->catalog(),
            'sections' => app(\FilaForge\Studio\Sections\SectionRegistry::class)->catalog($this->editorContext()),
            'widgets' => $widgets->reject(fn ($widget) => $widget->name() === 'data-pagination')->map(fn ($widget) => [
                'type' => $widget->name(),
                'label' => match ($widget->name()) {
                    'html' => __('filaforge::studio.builder.html'),
                    'shared-section' => __('filaforge::studio.builder.shared_section'),
                    default => $widget->name(),
                },
                'props' => $widget->defaults(),
                'children' => $widget->acceptsChildren(),
                'inspector' => $widget instanceof \FilaForge\Studio\Contracts\InspectorWidget,
            ])->values()->all(),
            'slots' => $widgets->map(fn ($widget, $name) => app(\FilaForge\Studio\DynamicData\BindingValidator::class)->slots($name))->all(),
        ];
    }
    public function previewDocument(array $document, array $pagination = []): array {
        $page=$this->page();
        $key='filaforge-preview:'.$page->id.':'.(auth()->id()??'guest');
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key,30)) return ['error'=>__('filaforge::studio.data_preview_failed')];
        \Illuminate\Support\Facades\RateLimiter::hit($key,60);
        try {
            if (count($pagination)>20) throw new \InvalidArgumentException('Too many pagination options.');
            foreach($pagination as $node=>$number) if(!is_string($node)||!preg_match('/^[A-Za-z0-9_-]{1,80}$/D',$node)||!is_int($number)||$number<1||$number>1000) throw new \InvalidArgumentException('Invalid preview page.');
            app(\FilaForge\Studio\Core\DocumentValidator::class)->validate($document);
            $base=$this->editorContext();
            $context=new \FilaForge\Studio\DynamicData\DataContext(locale:$document['locale'],page:$page,tenantKey:$base->tenantKey,recordKey:$base->recordKey,user:$base->user,publicAudience:$base->publicAudience,pagination:$pagination);
            $html=app(\FilaForge\Studio\Rendering\HtmlRenderer::class)->document($document, new \FilaForge\Studio\Rendering\HtmlOptions(title:$page->title), $page, $context)->toHtml();
            $message=json_encode(__('filaforge::studio.data_preview_only'),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
            $bridge='<script>document.addEventListener("click",function(e){var a=e.target.closest("a[data-ff-page]");if(!a)return;e.preventDefault();parent.postMessage({type:"filaforge-preview-page",node:a.dataset.ffLoop,page:Number(a.dataset.ffPage)},"*")});document.addEventListener("submit",function(e){e.preventDefault();e.stopImmediatePropagation();var s=e.target.querySelector("[data-comment-status]");if(s)s.textContent='.$message.'},true);</script>';
            return ['html'=>str_replace('</body>',$bridge.'</body>',$html)];
        } catch (\Throwable $error) { report($error); return ['error'=>__('filaforge::studio.data_preview_failed')]; }
    }
    public function makeDynamicElement(string $kind,string $source,string $name,array $arguments=[]): array {
        $this->page();
        $key='filaforge-insert:'.$this->pageId.':'.auth()->id();
        if(\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key,60))return ['error'=>__('filaforge::studio.picker_failed')];
        \Illuminate\Support\Facades\RateLimiter::hit($key,60);
        try {return ['node'=>app(\FilaForge\Studio\DynamicData\DynamicElementFactory::class)->make($kind,$source,$name,$arguments)];}
        catch(\InvalidArgumentException $error){return ['error'=>__('filaforge::studio.picker_failed')];}
    }
    public function importShortcodes(string $text): array {
        $page=$this->page();
        try { return ['document'=>app(\FilaForge\Studio\DynamicData\ShortcodeParser::class)->parse($text,$page->locale,$page->direction)]; }
        catch (\InvalidArgumentException $error) { return ['error'=>__('filaforge::studio.data_shortcode_invalid')]; }
    }
    private function editorContext(): \FilaForge\Studio\DynamicData\DataContext
    {
        $page = $this->page();
        $resolver = config('filaforge.editor_context');
        return $resolver ? app($resolver)($page, auth()->user()) : new \FilaForge\Studio\DynamicData\DataContext(locale:$page->locale, page:$page);
    }

    public function makeRepeater(string $source, array $fields, array $query = [], int $columns = 3): array
    {
        $this->page();
        try { return ['node'=>app(\FilaForge\Studio\DynamicData\DynamicElementFactory::class)->repeater($source,$fields,$query,$columns)]; }
        catch (\InvalidArgumentException) { return ['error'=>__('filaforge::studio.builder.repeater_invalid')]; }
    }

    public function sanitizeHtml(string $html): string
    {
        $this->page();
        if (strlen($html) > 100000) throw new \InvalidArgumentException('HTML is too large.');
        return app(\FilaForge\Studio\Rendering\SafeHtml::class)->clean($html);
    }

    public function revisionHistory(): array
    {
        return $this->page()->revisions()->latest('created_at')->orderByDesc('version')->limit(30)->get(['id','version','created_at'])->toArray();
    }

    public function revisionDocument(string $id): array
    {
        return $this->page()->revisions()->whereKey($id)->firstOrFail()->document;
    }

    public function restoreRevision(string $id, int $version): array
    {
        try {
            $version = app(PageDocuments::class)->restore($this->page(),$id,$version);
            return ['version'=>$version,'document'=>$this->page()->draft_document];
        } catch (\InvalidArgumentException|\RuntimeException $error) { return ['error'=>$error->getMessage()]; }
    }

    public function customForm(Schema $schema): Schema
    {
        $fields = [];
        if ($this->customWidgetType !== '') {
            $widget = app(\FilaForge\Studio\Core\WidgetRegistry::class)->get($this->customWidgetType);
            if ($widget instanceof \FilaForge\Studio\Contracts\InspectorWidget) $fields = $widget->inspector();
        }
        return $schema->components($fields)->statePath('customSettings');
    }

    public function configureWidget(string $type, array $props): void
    {
        $this->page();
        $widget = app(\FilaForge\Studio\Core\WidgetRegistry::class)->get($type);
        abort_unless($widget instanceof \FilaForge\Studio\Contracts\InspectorWidget, 422);
        $widget->validate($props);
        $this->customWidgetType = $type;
        $this->cacheSchema('customForm', null);
        $this->customForm->fill($props);
    }

    public function validatedCustomSettings(): array
    {
        $this->page();
        $props = $this->customForm->getState();
        app(\FilaForge\Studio\Core\WidgetRegistry::class)->get($this->customWidgetType)->validate($props);
        return $props;
    }

}
