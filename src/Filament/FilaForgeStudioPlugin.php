<?php

namespace FilaForge\Studio\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Resources\Resource;

final class FilaForgeStudioPlugin implements Plugin
{
    private array $resourceOverrides = [];

    public static function make(): self { return new self; }

    public function getId(): string { return 'filaforge-studio'; }

    public function pageResource(?string $resource): self
    {
        $this->resourceOverrides['pages'] = $resource;
        return $this;
    }

    public function commentResource(?string $resource): self
    {
        $this->resourceOverrides['comments'] = $resource;
        return $this;
    }

    public function getResources(): array
    {
        $resources = array_replace([
            'pages' => Resources\PageResource::class,
            'comments' => Resources\CommentResource::class,
        ], config('filaforge.resources', []), $this->resourceOverrides);

        foreach ($resources as $resource) {
            if ($resource !== null && (!is_string($resource) || !is_subclass_of($resource, Resource::class))) {
                throw new \InvalidArgumentException('FilaForge resources must be Filament Resource classes or null.');
            }
        }

        return array_values(array_unique(array_filter($resources, fn ($resource) => $resource !== null)));
    }

    public function register(Panel $panel): void
    {
        $panel->middleware([\FilaForge\Studio\Http\Middleware\StudioSecurity::class])
            ->resources($this->getResources());
    }

    public function boot(Panel $panel): void {}
}
