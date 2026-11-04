<?php

namespace FilaForge\Studio\Tests\Feature;

use FilaForge\Studio\Core\WidgetRegistry;
use FilaForge\Studio\Filament\Resources\Pages\EditPage;
use FilaForge\Studio\Tests\AuthoringTestCase;

final class WidgetInspectorTest extends AuthoringTestCase
{
    public function test_custom_widget_inspector_uses_filament_validation_and_stays_in_registry(): void
    {
        $this->admin();
        $widget = new \FilaForge\Studio\Tests\Fixtures\BadgeWidget;
        app(WidgetRegistry::class)->register($widget);
        $panel=\Filament\Panel::make()->id('authoring')->path('authoring')->plugin(\FilaForge\Studio\Filament\FilaForgeStudioPlugin::make());
        \Filament\Facades\Filament::registerPanel($panel);\Filament\Facades\Filament::setCurrentPanel($panel);
        \Illuminate\Support\Facades\Route::name('filament.authoring.')->group(fn()=>\FilaForge\Studio\Filament\Resources\PageResource::registerRoutes($panel));
        $page=$this->page($this->doc([$this->node('badge')]));
        $component=\Livewire\Livewire::test(EditPage::class,['record'=>$page->id])
            ->call('configureWidget','badge',['text'=>'Before'])
            ->set('customSettings.text','After')
            ->call('validatedCustomSettings')->assertReturned(['text'=>'After'])
            ->set('customSettings.text','')->call('validatedCustomSettings')->assertHasErrors(['customSettings.text'=>'required']);
        if ($directory=getenv('FILAFORGE_BROWSER_OUTPUT')) {
            if (!is_dir($directory)) mkdir($directory,0777,true);
            file_put_contents($directory.'/editor.html',$component->html());
            file_put_contents($directory.'/catalog.json',json_encode($this->editor($page)->dynamicCatalog(),JSON_THROW_ON_ERROR));
            file_put_contents($directory.'/initial.json',json_encode($this->editor($page)->initialState(),JSON_THROW_ON_ERROR));
            file_put_contents($directory.'/canvas.html',view('filaforge::editor.canvas')->render());
        }
    }
}
