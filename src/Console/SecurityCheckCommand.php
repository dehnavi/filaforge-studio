<?php
namespace FilaForge\Studio\Console;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
final class SecurityCheckCommand extends Command
{
    protected $signature='filaforge:security-check';
    protected $description='Check host session and deployment security settings without changing them';
    public function handle(): int
    {
        $checks=[
            'Session cookie is HttpOnly'=>config('session.http_only')===true,
            'Session SameSite is lax or strict'=>in_array(config('session.same_site'),['lax','strict'],true),
            'Session cookie is Secure over HTTPS'=>config('session.secure')===true,
            'Application URL uses HTTPS'=>str_starts_with((string)config('app.url'),'https://'),
            'Debug output is disabled'=>config('app.debug')===false,
        ];
        try {
            $checks['FilaForge migrations are applied'] = collect(['pages', 'page_locales', 'revisions', 'comments', 'media', 'presets', 'media_folders'])
                ->every(fn (string $table) => Schema::hasTable(config('filaforge.table_prefix').$table));
        } catch (\Throwable) {
            $checks['FilaForge migrations are applied'] = false;
        }
        if (config('filaforge.published_assets')) {
            $checks['Published FilaForge assets exist'] = is_file(public_path('vendor/filaforge/components.js')) && is_file(public_path('vendor/filaforge/fontawesome.json'));
        }
        $mode = (string) config('filaforge.authorization.mode', 'host');
        $this->line('INFO: authorization mode is '.$mode);
        $this->line('INFO: configured resources: '.implode(', ', array_keys(array_filter(config('filaforge.resources', []), fn ($resource) => $resource !== null))));
        foreach (['filaforge.viewAny', 'filaforge.create', 'filaforge.update', 'filaforge.publish', 'filaforge.moderateAllComments'] as $ability) {
            $this->line('INFO: Gate '.$ability.': '.(Gate::has($ability) ? 'defined' : 'not defined (deny in host mode)'));
        }
        $this->line('INFO: published assets: '.(config('filaforge.published_assets') ? 'enabled' : 'disabled'));
        try {
            $panels = \Filament\Facades\Filament::getPanels();
            $registered = collect($panels)->filter(fn ($panel) => $panel->hasPlugin('filaforge-studio'))->count();
            $this->line('INFO: panels with FilaForge plugin: '.$registered.'/'.count($panels));
            foreach ($panels as $panel) {
                if ($panel->hasPlugin('filaforge-studio')) {
                    $this->line('INFO: panel '.$panel->getId().' authorization mode: '.$panel->getPlugin('filaforge-studio')->getAuthorizationMode());
                }
            }
        } catch (\Throwable) {
            $this->line('INFO: Filament panel registration could not be inspected in this runtime.');
        }
        $this->line('INFO: published asset files are checked only when published_assets is enabled.');
        foreach($checks as $label=>$ok){$ok?$this->line('PASS: '.$label):$this->warn('REVIEW: '.$label);}
        $this->line('Authentication/session rotation and revocation remain the host authentication provider responsibility. This command changes no settings.');
        return in_array(false,$checks,true)?self::FAILURE:self::SUCCESS;
    }
}
