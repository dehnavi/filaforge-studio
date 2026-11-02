<?php
namespace FilaForge\Studio\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
final class Preset extends Model {use HasUlids;protected $guarded=['id'];protected function casts():array{return ['root'=>'array'];}public function getTable(){return config('filaforge.table_prefix').'presets';}}
