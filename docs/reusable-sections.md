---
title: Reusable Sections and Database Repeaters
description: Embed independent published sections, share content, build database cards, and control client editing in FilaForge Studio.
---

# Reusable sections and database repeaters

## Embed published content anywhere

A page locale can hold an entire page or a small section. Query and authorize it in your host application, including tenant constraints, then embed it in any Blade view:

```blade
<header>Existing site header</header>
{{ filaforge_section($authorizedPageLocale) }}
<article>Existing application content</article>
{{ filaforge_section($anotherAuthorizedPageLocale) }}
```

No particular layout, catch-all route, or document head is required. The helper reads only the exact published revision; an unpublished section returns 404. Page integrations are not injected by this fragment helper. PHP/Blade strings stored in content never execute.

Each instance receives independent node IDs, responsive selectors, generated component IDs, and local section-anchor links. You can pass a unique, stable instance name as the third argument. Use a different name for each occurrence on the same page. Existing `filaforge_html()` remains compatible and does not automatically namespace its IDs.

For several fragments, an optional response-local asset collector removes duplicate identical style and external script tags. Instance-specific CSS is retained:

```php
use FilaForge\Studio\Rendering\RenderAssets;

$assets = new RenderAssets;
$first = filaforge_section($firstPage, $context, 'first-section', $assets);
$second = filaforge_section($secondPage, $context, 'second-section', $assets);
```

```blade
{{ $first }}
{{ $second }}
{{ $assets->html() }}
```

Render the collector after collecting every fragment, anywhere appropriate in the host view. Never share a collector across requests. This deduplicates identical asset tags, not arbitrary custom widget dependencies. Public caching must still include the tenant, audience and published content dependencies.

## Linked shared sections

Manage the source as an ordinary FilaForge page locale. Register a public alias in a host service provider's `boot()` method:

```php
use FilaForge\Studio\DynamicData\DataContext;
use FilaForge\Studio\Models\PageLocale;
use FilaForge\Studio\Sections\SectionRegistry;

app(SectionRegistry::class)->register(
    key: 'service-guarantee',
    label: 'Service guarantee',
    resolve: fn (DataContext $context) => PageLocale::query()
        // Apply your real tenant/visibility constraints here.
        ->where('locale', $context->locale)
        ->where('slug', 'service-guarantee')
        ->firstOrFail(),
    authorize: fn (DataContext $context) => $context->publicAudience,
);
```

The editor's **Shared section** widget stores the alias. All linked instances render the source's published revision, so saving its draft changes none of them; publishing updates them on their next uncached render. A copied preset remains an independent copy.

Authorization runs before the resolver is called. A resolved source is reused only within the current render and identical locale, tenant, record, identity, audience and row context. Missing, denied, unpublished or mismatched-locale sources render no content. Recursion is bounded by the dynamic engine's depth, node and time budgets. Shared sources may contain repeaters and other registered shared sources. You can expose selected source properties as per-instance overrides when registering the alias:

```php
fields: [
    'headline' => ['node'=>'source-heading-id', 'property'=>'text', 'label'=>'Headline', 'type'=>'text'],
    'photo' => ['node'=>'source-image-id', 'property'=>'src', 'label'=>'Image URL or media reference', 'type'=>'image'],
],
```

Use the source document's stable node IDs. In an instance's settings, enable an override and enter its value. Disabling it restores the source value. Only registered properties are overridable; structural changes and unknown fields are rejected. The overridden property's old formatting and binding are removed in that render, so they cannot overwrite its explicit value. Source documents and other instances remain unchanged. If a source node is deleted or the override becomes invalid, that instance fails closed instead of exposing unvalidated markup.

## Database repeaters

Register an `EloquentDataProvider` in trusted application code; the editor never receives model, table or column names. For example:

```php
use App\Models\Product;
use FilaForge\Studio\DynamicData\{DataContext, DataRegistry, EloquentDataProvider};

app(DataRegistry::class)->register(new EloquentDataProvider(
    id: 'products',
    title: 'Products',
    schema: [
        'title' => ['label'=>'Title', 'type'=>'text'],
        'price' => ['label'=>'Price', 'type'=>'number'],
        'photo' => ['label'=>'Photo', 'type'=>'image', 'attribute'=>'image_url'],
    ],
    scope: fn (DataContext $context) => Product::query()
        ->where('tenant_id', $context->tenantKey)
        ->where('published', true),
    access: fn (DataContext $context) => $context->tenantKey !== null,
    public: true,
    sortDefinitions: [
        'newest' => ['label'=>'Newest', 'column'=>'id', 'direction'=>'desc'],
    ],
    limit: 12,
));
```

Open **Database repeater**, select the source, check the card's fields, and choose 1–6 columns. The editor creates a normal query loop with a card template and `@item` bindings. You can rearrange and style the card's child widgets. Images become image widgets; URLs become links; other fields become text. Tablet and mobile column overrides are included. Pagination spans the full grid width.

Configure allowed filters, sorting, page size and empty-state text in **Dynamic content**. Preview resolves real records through the same bounded engine as public rendering. Private sources cannot render to a public audience merely because the editor is logged in.

For tenant-aware editor previews, set `filaforge.editor_context` to a container-resolvable invokable class returning a `DataContext` from the authorized `PageLocale` and current user. It must derive tenant and audience from trusted server state, never request JSON. The default preview context is public with no tenant key. Pass the corresponding context when rendering public fragments:

```blade
{{ filaforge_section($page, $context) }}
```

Read [dynamic content](dynamic-content.md) for provider filters, access rules and eager-loaded relationship fields. Repeater templates can also be created with `DynamicElementFactory::repeater($source, $fields, $query, $columns)`.

## HTML block

The **HTML block** widget inserts markup at the selected container, independently from HTML-to-widget import. Apply HTML to sanitize and update the canvas. Server rendering always sanitizes again. Safe structural markup, including tables, is supported; executable scripts, event handlers, inline CSS and unsafe URLs are removed. Stored markup is never compiled as Blade or PHP. Arbitrary CSS/JavaScript embedding is not part of this widget.

## Shared design values

Configure `filaforge.design_tokens` with typed values:

```php
'design_tokens' => [
    'brand' => ['label'=>'Brand text', 'property'=>'color', 'value'=>'#234567'],
    'section-space' => ['label'=>'Section spacing', 'property'=>'padding', 'value'=>32],
],
```

Choose a token under **Shared styles**. Style tokens cover background/text/border colors, font family and border radius. Responsive token controls cover padding, gap, font size and maximum width for the selected viewport. Saved documents retain references such as `['token'=>'brand']`; canvas and server rendering resolve current values. Changing configuration updates every uncached render using that token. Ordinary local controls replace the reference with an explicit local value. Tokens must resolve to values accepted by the existing style validator; incompatible properties and unsafe values are rejected.

## Client editing permissions

Only users granted `filaforge.manageStructure` for the page may introduce or change editing rules. Grant it explicitly to trusted designers. Existing page update and publication permissions remain necessary.

Under **Client editing permissions**, a designer may name a layer, lock its entire subtree, or expose specific property keys. An exposed-field element keeps its children, styles, bindings, type and position fixed; only the selected properties and their associated formatted text may change. An empty exposed-field list makes it read-only. For a fully fixed region, lock its parent. Unrestricted elements retain ordinary editing behavior.

The server compares against the stored draft, so removing metadata, moving a protected subtree, deleting it, or submitting forged JSON cannot bypass these restrictions. A structure manager may intentionally change or remove them. These are editing controls, not tenant authorization or a substitute for page policies.

## Autosave, recovery and versions

Autosave is enabled by default with a 1,500 ms debounce. Disable it with `filaforge.autosave`, or adjust `filaforge.autosave_delay` (minimum 500 ms). The editor serializes saves and preserves edits made while a save is in flight. A failed or conflicting save pauses automatic retries and retains local changes; use **Save** to retry after resolving the cause. Publication remains explicit.

Recovery uses tab-local `sessionStorage`, keyed by host, user and page. It is best-effort browser storage, not a backup or cross-device synchronization. Recovery requires the same server version; a stale recovery cannot overwrite a newer draft. Successful saves remove the recovery copy. Avoid editing highly sensitive documents on shared browser profiles.

**Version history** lists the latest 30 publication snapshots. Compare shows the selected JSON document alongside the current draft. Restoring checks the page, permissions, editing rules and expected version, replaces only the draft, and never republishes it automatically. Existing revisions are retained; automatic pruning is not enabled.

Element copying also uses tab-local storage scoped to the host and user, allowing copy/paste between pages opened in the same tab. IDs are regenerated on paste. Style copy/paste transfers styles and responsive overrides. Server validation and editing restrictions still apply.

## Custom Filament inspectors

A registered custom widget may implement `Contracts\InspectorWidget` in addition to the usual widget behavior:

```php
public function inspector(): array
{
    return [\Filament\Forms\Components\TextInput::make('text')
        ->required()->maxLength(100)];
}
```

Its registered defaults appear in the widget library. The editor renders the trusted Filament schema and validates its state before applying it. Continue validating properties in `Widget::validate()` because documents can arrive through imports and API calls. Register dynamic binding slots separately if needed. Arbitrary custom widget markup is available in server preview; custom canvas renderers are not introduced by this API.
