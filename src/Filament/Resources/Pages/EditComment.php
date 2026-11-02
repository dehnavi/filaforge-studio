<?php
namespace FilaForge\Studio\Filament\Resources\Pages;
final class EditComment extends \Filament\Resources\Pages\EditRecord {
 protected static string $resource=\FilaForge\Studio\Filament\Resources\CommentResource::class;
 protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record,array $data):\Illuminate\Database\Eloquent\Model{\Illuminate\Support\Facades\Gate::authorize('update',$record);$data=\Illuminate\Support\Facades\Validator::make(array_intersect_key($data,array_flip(['name','body','status'])),['name'=>'required|string|max:100','body'=>'required|string|max:3000','status'=>'required|in:pending,approved,rejected'])->validate();$record->forceFill($data)->save();return $record;}
}
