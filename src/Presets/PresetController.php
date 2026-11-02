<?php
namespace FilaForge\Studio\Presets;
use Illuminate\Http\Request;
use FilaForge\Studio\Core\DocumentValidator;
use FilaForge\Studio\Models\Preset;
final class PresetController {
 public function store(Request $request){$data=$request->validate(['label'=>'required|string|max:100','category'=>'required|string|max:50','root'=>'required|array']);$document=DocumentValidator::empty();$document['root']['id']='preset-envelope';$document['root']['children']=[$data['root']];try{app(DocumentValidator::class)->validate($document);app(\FilaForge\Studio\Media\MediaReferences::class)->validate($document);}catch(\InvalidArgumentException $e){throw \Illuminate\Validation\ValidationException::withMessages(['root'=>$e->getMessage()]);}$preset=Preset::create($data);return response()->json(['key'=>'custom-'.strtolower($preset->id),'label'=>$preset->label,'category'=>$preset->category,'root'=>$preset->root],201);}
}
