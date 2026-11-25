<?php

namespace FilaForge\Studio\Tests\Feature;

use FilaForge\Studio\Filament\FilaForgeStudioPlugin;
use FilaForge\Studio\Filament\Resources\{CommentResource, PageResource};
use FilaForge\Studio\Tests\TestCase;
use Filament\Panel;
use Filament\Facades\Filament;

final class ResourceAuthorizationTest extends TestCase
{
    private function usePanel(?FilaForgeStudioPlugin $plugin = null): void
    {
        $panel = Panel::make()->id('auth')->path('auth')->default()->plugin($plugin ?? FilaForgeStudioPlugin::make());
        Filament::registerPanel($panel);
        Filament::setCurrentPanel($panel);
    }

    public function test_host_mode_hides_page_resource_without_grant(): void
    {
        config(['filaforge.authorization.mode' => 'host']);
        $this->usePanel();
        $this->actingAs($this->user(['email' => 'editor@example.test']));

        $this->assertFalse(PageResource::canViewAny());
    }

    public function test_host_gate_makes_page_resource_visible(): void
    {
        config(['filaforge.authorization.mode' => 'host']);
        $this->usePanel();
        $this->actingAs($this->user(['email' => 'admin@filaforge.test']));

        $this->assertTrue(PageResource::canViewAny());
    }

    public function test_comment_resource_requires_global_moderation_gate(): void
    {
        $this->usePanel();
        $this->actingAs($this->user(['email' => 'editor@example.test']));
        $this->assertFalse(CommentResource::canViewAny());
        $this->actingAs($this->user(['email' => 'admin@filaforge.test']));
        // The fixture's moderation Gate is intentionally not defined by default.
        $this->assertFalse(CommentResource::canViewAny());
    }

    public function test_guests_are_denied_even_in_authenticated_mode(): void
    {
        config(['filaforge.authorization.mode' => 'authenticated']);
        $this->usePanel();
        $this->assertFalse(PageResource::canViewAny());
        $this->assertFalse(CommentResource::canViewAny());
    }

    public function test_default_mode_is_host_and_panel_can_override_it(): void
    {
        config(['filaforge.authorization.mode' => 'host']);
        $this->assertSame('host', FilaForgeStudioPlugin::make()->getAuthorizationMode());
        $plugin = FilaForgeStudioPlugin::make()->authorizationMode('authenticated');
        $this->assertSame('authenticated', $plugin->getAuthorizationMode());
        $this->expectException(\InvalidArgumentException::class);
        FilaForgeStudioPlugin::make()->authorizationMode('public');
    }

    public function test_disabled_resources_are_not_registered(): void
    {
        $panel = Panel::make()->id('disabled')->plugin(
            FilaForgeStudioPlugin::make()->disablePageResource()->disableCommentResource(),
        );

        $this->assertSame([], $panel->getResources());
    }

    public function test_install_and_security_commands_explain_authorization_state(): void
    {
        $this->artisan('filaforge:install')
            ->expectsOutputToContain('FilaForge installation guidance')
            ->expectsOutputToContain("Gate::define('filaforge.viewAny'")
            ->assertExitCode(0);
        $this->artisan('filaforge:security-check')
            ->expectsOutputToContain('INFO: authorization mode is host')
            ->expectsOutputToContain('INFO: Gate filaforge.viewAny: defined')
            ->assertExitCode(1);
    }
}
