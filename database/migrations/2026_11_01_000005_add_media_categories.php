<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table(config('filaforge.table_prefix').'media',fn(Blueprint $t)=>$t->string('category',80)->default('')->index()); }
 public function down(): void { Schema::table(config('filaforge.table_prefix').'media',fn(Blueprint $t)=>$t->dropColumn('category')); }
};
