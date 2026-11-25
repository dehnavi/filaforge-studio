<?php

namespace FilaForge\Studio\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Resources\Resource;

final class FilaForgeStudioPlugin implements Plugin
{
    private array $resourceOverrides = [];
    private ?string $authorizationModeOverride = null;

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

    public function enablePageResource(): self { $this->resourceOverrides['pages'] = config('filaforge.resources.pages', Resources\PageResource::class) ?: Resources\PageResource::class; return $this; }
    public function disablePageResource(): self { $this->resourceOverrides['pages'] = null; return $this; }
    public function enableCommentResource(): self { $this->resourceOverrides['comments'] = config('filaforge.resources.comments', Resources\CommentResource::class) ?: Resources\CommentResource::class; return $this; }
    public function disableCommentResource(): self { $this->resourceOverrides['comments'] = null; return $this; }
    public function authorizationMode(?string $mode): self
    {
        if ($mode !== null && ! in_array($mode, ['host', 'authenticated'], true)) throw new \InvalidArgumentException("FilaForge authorization mode must be 'host' or 'authenticated'.");
        $this->authorizationModeOverride = $mode;
        return $this;
    }
    public function getAuthorizationMode(): string { return $this->authorizationModeOverride ?? (string) config('filaforge.authorization.mode', 'host'); }

    public static function currentAuthorizationMode(): string
    {
        $mode = (string) config('filaforge.authorization.mode', 'host');
        try {
            $panel = \Filament\Facades\Filament::getCurrentPanel();
            $plugin = $panel->getPlugin('filaforge-studio');
            if ($plugin instanceof self) $mode = $plugin->getAuthorizationMode();
        } catch (\Throwable) {
            // Policy checks outside a serving panel use the application config.
        }
        return $mode;
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
        if (! in_array($this->getAuthorizationMode(), ['host', 'authenticated'], true)) throw new \InvalidArgumentException("FilaForge authorization mode must be 'host' or 'authenticated'.");
        $panel->middleware([\FilaForge\Studio\Http\Middleware\StudioSecurity::class])
            ->resources($this->getResources());
    }

    public function boot(Panel $panel): void {}
}
