<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::create(config('filaforge.table_prefix').'presets',function(Blueprint $t){$t->ulid('id')->primary();$t->string('label',100);$t->string('category',50);$t->json('root');$t->unsignedInteger('schema_version')->default(1);$t->timestamps();});}public function down():void{Schema::dropIfExists(config('filaforge.table_prefix').'presets');}};
