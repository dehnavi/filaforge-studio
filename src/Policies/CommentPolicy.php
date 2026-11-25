<?php
namespace FilaForge\Studio\Policies;
use FilaForge\Studio\Models\{Comment,PageLocale};
use Illuminate\Support\Facades\Gate;
use FilaForge\Studio\Filament\FilaForgeStudioPlugin;
final class CommentPolicy
{
    // Listing all comments is an explicit global grant, not an editor permission.
    public function viewAny($user): bool {return $this->allows($user, 'moderateAllComments');}
    public function view($user,Comment $comment): bool {return $this->update($user,$comment);}
    public function update($user,Comment $comment): bool
    {
        $page=PageLocale::whereHas('page')->find($comment->page_locale_id);
        return $page!==null && ($this->viewAny($user)||$this->allows($user, 'moderateComments', $page));
    }
    public function create($user): bool {return false;}
    public function delete($user,Comment $comment): bool {return false;}
    private function allows($user, string $ability, mixed $record = null): bool
    {
        if ($user === null) return false;
        $name = 'filaforge.'.$ability;
        if (Gate::has($name)) return $record === null ? Gate::forUser($user)->allows($name) : Gate::forUser($user)->allows($name, $record);
        return FilaForgeStudioPlugin::currentAuthorizationMode() === 'authenticated';
    }
}
