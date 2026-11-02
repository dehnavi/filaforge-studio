<?php
namespace FilaForge\Studio\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
final class PageLocale extends Model {
    use HasUlids;
    protected $guarded = ['id','lock_version','published_revision_id'];
    protected function casts(): array { return ['draft_document'=>'array','lock_version'=>'integer']; }
    public function getTable() { return config('filaforge.table_prefix').'page_locales'; }
    public function page() { return $this->belongsTo(Page::class); }
    public function revisions() { return $this->hasMany(Revision::class); }
    public function publishedRevision() { return $this->belongsTo(Revision::class,'published_revision_id'); }
}
