<?php
namespace FilaForge\Studio\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
final class Page extends Model {
    use HasUlids, SoftDeletes;
    protected $guarded = ['id'];
    public function getTable() { return config('filaforge.table_prefix').'pages'; }
    public function locales() { return $this->hasMany(PageLocale::class); }
}
