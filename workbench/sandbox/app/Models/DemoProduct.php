<?php
namespace App\Models;
final class DemoProduct extends \Illuminate\Database\Eloquent\Model {
 protected $table='filaforge_demo_products'; protected $guarded=[]; public $timestamps=false;
 protected function casts(): array { return ['available'=>'boolean','price'=>'float']; }
 public function getPublicUrlAttribute(): string {return '/preview/dynamic-demo#product-'.$this->id;}
}
