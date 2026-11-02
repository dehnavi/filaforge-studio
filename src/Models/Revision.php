<?php
namespace FilaForge\Studio\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
final class Revision extends Model {
    use HasUlids;
    protected $guarded = ['id'];
    protected function casts(): array { return ['document'=>'array']; }
    public function getTable() { return config('filaforge.table_prefix').'revisions'; }
    protected static function booted(): void { static::updating(fn () => throw new \LogicException('Revisions are immutable.')); }
}
