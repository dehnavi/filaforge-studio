<?php
namespace FilaForge\Studio\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
final class Comment extends Model {use HasUlids;protected $guarded=['id','status'];public function getTable(){return config('filaforge.table_prefix').'comments';}}
