<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        $p = config('filaforge.table_prefix');
        Schema::create($p.'pages', function (Blueprint $t) { $t->ulid('id')->primary(); $t->timestamps(); $t->softDeletes(); });
        Schema::create($p.'page_locales', function (Blueprint $t) use ($p) {
            $t->ulid('id')->primary(); $t->foreignUlid('page_id')->constrained($p.'pages')->cascadeOnDelete();
            $t->string('locale', 12); $t->string('title'); $t->string('slug'); $t->string('direction', 3)->default('ltr');
            $t->json('draft_document'); $t->unsignedInteger('lock_version')->default(0); $t->ulid('published_revision_id')->nullable(); $t->timestamps();
            $t->unique(['page_id','locale']); $t->unique(['locale','slug']);
        });
        Schema::create($p.'revisions', function (Blueprint $t) use ($p) {
            $t->ulid('id')->primary(); $t->foreignUlid('page_locale_id')->constrained($p.'page_locales')->cascadeOnDelete();
            $t->json('document'); $t->unsignedInteger('version'); $t->timestamps();
        });
    }
    public function down(): void { $p=config('filaforge.table_prefix'); foreach (['revisions','page_locales','pages'] as $name) Schema::dropIfExists($p.$name); }
};
