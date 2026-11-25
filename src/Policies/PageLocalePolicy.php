<?php
namespace FilaForge\Studio\Policies;
use Illuminate\Support\Facades\Gate;
use FilaForge\Studio\Filament\FilaForgeStudioPlugin;
final class PageLocalePolicy {
    public function before($user, string $ability): ?bool {
        if ($user === null) return false;
        $hostAbility = 'filaforge.'.$ability;
        if (Gate::has($hostAbility)) return Gate::forUser($user)->allows($hostAbility) ? true : false;
        return FilaForgeStudioPlugin::currentAuthorizationMode() === 'authenticated' ? true : null;
    }
    public function viewAny($user): bool { return false; }
    public function view($user,$page): bool { return false; }
    public function create($user): bool { return false; }
    public function update($user,$page): bool { return false; }
    public function delete($user,$page): bool { return false; }
    public function publish($user,$page): bool { return false; }
}
