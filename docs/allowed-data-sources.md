---
title: Connect Trusted Laravel Data Sources
description: Expose selected Laravel model fields to FilaForge pages through explicit providers, tenant-scoped queries, allowlisted filters, and authorization.
---

# Connect Trusted Data Sources

FilaForge does not browse application tables. A developer registers each permitted data source in trusted Laravel code. Editors select source and field aliases; documents never choose model classes, table names, columns, SQL, or relationships.

## Define an Eloquent provider

This example exposes public fields from published products. Replace the model, tenant field, and rules with your application's actual schema:

```php
use App\Models\Product;
use FilaForge\Studio\DynamicData\DataRegistry;
use FilaForge\Studio\DynamicData\EloquentDataProvider;

app(DataRegistry::class)->register(new EloquentDataProvider(
    id: 'products',
    title: __('catalog.products'),
    schema: [
        'name' => ['label' => __('catalog.name'), 'type' => 'text'],
        'price' => ['label' => __('catalog.price'), 'type' => 'number'],
        'url' => ['label' => __('catalog.product_url'), 'type' => 'url', 'attribute' => 'public_url'],
    ],
    scope: fn ($context) => Product::query()
        ->where('tenant_id', $context->tenantKey)
        ->where('published', true),
    access: fn ($context) => $context->tenantKey !== null,
    public: true,
    filterDefinitions: [
        'category' => ['column' => 'category_id', 'rules' => ['integer', 'min:1'], 'operator' => 'eq'],
    ],
    sortDefinitions: [
        'price' => ['label' => __('catalog.price'), 'column' => 'price', 'direction' => 'asc'],
    ],
    limit: 24,
));
```

The public field alias `name` maps to a model attribute. The schema controls what the renderer can receive; add a SQL `select()` to the host query if you also want to limit selected columns. Keep the `scope` mandatory and include publication and tenant filters there. Trusted context must come from host code, never page JSON.

The `public: true` and access closure above explicitly permit publishing product fields. Do not use this pattern for users, orders, payments, or private data without a dedicated access policy.

## Filters and sorting

Filter and sort identifiers are fixed in PHP. The editor can choose an identifier and value; it cannot replace its column, operator, or sort direction. Supported Eloquent filter operators are `eq`, `contains`, and `in`. Values use Eloquent bindings. Validate each value with Laravel rules and enforce tenant/status constraints in `scope` independently of optional filters.

Cap the result count with `limit` (maximum 24). Search filters may use `%` and `_` as SQL `LIKE` wildcards; they are not executable SQL but retain wildcard semantics.

## Private or record-specific sources

Set `public: false` for private sources and require explicit authorization:

```php
access: fn ($context) => $context->user !== null
    && $context->tenantKey !== null
    && $context->user->can('readPrivateCatalog'),
public: false,
```

`readPrivateCatalog` is an example host ability. It must verify user ownership and tenant access. User authentication alone is not permission to read every record.

Pass trusted context from an authorized host route:

```php
use FilaForge\Studio\DynamicData\DataContext;

$context = new DataContext(
    locale: $page->locale,
    page: $page,
    tenantKey: (string) $authorizedTenant->getKey(),
    recordKey: (string) $authorizedRecord->getKey(),
    user: $request->user(),
    publicAudience: false,
);

return app(\FilaForge\Studio\Rendering\HtmlRenderer::class)
    ->publishedResponse($page, context: $context);
```

The host must authorize the tenant and record before constructing this context. Public routes should pass a domain-resolved tenant and `publicAudience: true`, without forwarding an administrator identity. Default editor preview has public audience and no user; it does not guess a tenant.

## Relationships and field types

Supported field types are `text`, `number`, `boolean`, `url`, `image`, and `date`. A singular relationship field such as `author.name` must be eager-loaded in the query. Lazy loading and to-many field traversal are rejected. Collection relationships should use a separate source and query loop.

Providers should return bounded scalar values or null. Do not return models, HTML strings, arbitrary objects, or secrets. URL values are validated again by the target widget. If accessors run queries, correct them before using them in a collection to avoid N+1 behavior.

## Register provider classes

Trusted provider implementations of `FilaForge\Studio\Contracts\DataProvider` can be listed in config:

```php
'data_providers' => [
    App\FilaForge\Data\ProductsProvider::class,
],
```

Provider aliases and fields appear in the editor's **Data & tools** tab after the app boots. Selecting a binding creates document structure; the provider is authorized and queried at render time. Missing source or denied access results in an empty/fallback state.

For shortcode components, loops, conditions, formatting, cache boundaries, and comments, see [Dynamic Content](dynamic-content.md). Presentation visibility is never a substitute for authorization.
