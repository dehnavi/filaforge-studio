<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{Schema::create(config('filaforge.table_prefix').'comments',function(Blueprint $t){$t->ulid('id')->primary();$t->foreignUlid('page_locale_id')->constrained(config('filaforge.table_prefix').'page_locales')->cascadeOnDelete();$t->string('node_id',80);$t->string('name',100);$t->text('body');$t->string('status',20)->default('pending')->index();$t->timestamps();});}
 public function down():void{Schema::dropIfExists(config('filaforge.table_prefix').'comments');}
};
