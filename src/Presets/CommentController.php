<?php
namespace FilaForge\Studio\Presets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Gate};
use FilaForge\Studio\Models\{PageLocale,Comment};
final class CommentController
{
    public function store(Request $request)
    {
        $data=$request->validate([
            'page'=>'required|ulid', 'node'=>['required','string','regex:/^[A-Za-z0-9_-]{1,80}$/D'],
            'name'=>'required|string|max:100', 'body'=>'required|string|max:3000', 'website'=>'nullable|string|max:0',
        ]);
        DB::transaction(function () use ($request,$data) {
            $page=PageLocale::whereHas('page')->lockForUpdate()->findOrFail($data['page']);
            Gate::forUser($request->user())->authorize('filaforge.comment', $page);
            // Never trust an unrelated revision pointer or a draft-only form.
            $revision=$page->revisions()->whereKey($page->published_revision_id)->first();
            abort_unless($revision,404);
            $found=false;$count=0;
            $walk=function(array $node,int $depth=0)use(&$walk,&$found,&$count,$data){
                abort_if($depth>32||++$count>1000,422);
                if($node['id']===$data['node']&&$node['type']==='comments')$found=true;
                foreach($node['children'] as $child)$walk($child,$depth+1);
            };
            $walk($revision->document['root']);abort_unless($found,404);
            Comment::create(['page_locale_id'=>$page->id,'node_id'=>$data['node'],'name'=>$data['name'],'body'=>$data['body'],'status'=>'pending']);
        });
        return response()->json(['message'=>__('filaforge::studio.comment_received')],201);
    }
}
