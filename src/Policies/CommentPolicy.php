<?php
namespace FilaForge\Studio\Policies;
use FilaForge\Studio\Models\{Comment,PageLocale};
use Illuminate\Support\Facades\Gate;
final class CommentPolicy
{
    // Listing all comments is an explicit global grant, not an editor permission.
    public function viewAny($user): bool {return Gate::forUser($user)->allows('filaforge.moderateAllComments');}
    public function view($user,Comment $comment): bool {return $this->update($user,$comment);}
    public function update($user,Comment $comment): bool
    {
        $page=PageLocale::whereHas('page')->find($comment->page_locale_id);
        return $page!==null && ($this->viewAny($user)||Gate::forUser($user)->allows('filaforge.moderateComments',$page));
    }
    public function create($user): bool {return false;}
    public function delete($user,Comment $comment): bool {return false;}
}
