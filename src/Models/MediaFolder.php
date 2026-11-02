<?php
namespace FilaForge\Studio\Models;
use Illuminate\Database\Eloquent\Model;
final class MediaFolder extends Model {
 protected $guarded=['id'];
 public function getTable(){return config('filaforge.table_prefix').'media_folders';}
}
