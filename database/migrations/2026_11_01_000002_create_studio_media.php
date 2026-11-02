<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create(config('filaforge.table_prefix').'media',function(Blueprint $t){$t->ulid('id')->primary();$t->string('name');$t->string('disk');$t->string('path');$t->string('mime');$t->unsignedBigInteger('bytes');$t->string('hash',64)->index();$t->unsignedInteger('width')->nullable();$t->unsignedInteger('height')->nullable();$t->string('alt')->default('');$t->text('caption')->nullable();$t->string('folder')->default('');$t->ulid('original_id')->nullable();$t->timestamps();$t->softDeletes();}); }
 public function down(): void { Schema::dropIfExists(config('filaforge.table_prefix').'media'); }
};
