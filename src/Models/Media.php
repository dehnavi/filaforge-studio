<?php
namespace FilaForge\Studio\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
final class Media extends Model {
 use HasUlids,SoftDeletes;
 protected $guarded=['id'];
 public function getTable(){return config('filaforge.table_prefix').'media';}
 protected function casts(): array {return ['bytes'=>'integer','width'=>'integer','height'=>'integer'];}
}
