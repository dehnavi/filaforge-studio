<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create(config('filaforge.table_prefix').'media_folders',function(Blueprint $t){$t->id();$t->string('path',300)->unique();$t->timestamps();}); }
 public function down(): void { Schema::dropIfExists(config('filaforge.table_prefix').'media_folders'); }
};
