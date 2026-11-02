<?php
use Illuminate\Support\Facades\Route;
use FilaForge\Studio\Media\MediaController;
Route::middleware(['web',\FilaForge\Studio\Http\Middleware\StudioSecurity::class,'auth','can:filaforge.update','throttle:60,1'])->prefix('filaforge-media')->group(function(){
 Route::post('/presets',[\FilaForge\Studio\Presets\PresetController::class,'store']);
 Route::get('/presets',fn()=>['presets'=>app(\FilaForge\Studio\Presets\PresetRegistry::class)->all()]);
 Route::get('/',[MediaController::class,'index']);
 Route::post('/',[MediaController::class,'upload']);
 Route::post('/folders',[MediaController::class,'createFolder']);
 Route::delete('/folders',[MediaController::class,'deleteFolder']);
 Route::patch('/bulk',[MediaController::class,'bulk']);
 Route::patch('/{media}',[MediaController::class,'update']);
 Route::post('/{media}/edit',[MediaController::class,'edit'])->middleware('throttle:10,1');
 Route::delete('/{media}',[MediaController::class,'destroy']);
});
Route::get('/filaforge-media/{media}/file',[MediaController::class,'file'])->middleware(['web','signed'])->name('filaforge.media.file');

Route::post('/filaforge-comments',[\FilaForge\Studio\Presets\CommentController::class,'store'])->middleware(['web',\FilaForge\Studio\Http\Middleware\StudioSecurity::class,'throttle:10,1'])->name('filaforge.comments.store');
