<?php
namespace FilaForge\Studio\Tests\Fixtures;
final class Product extends \Illuminate\Database\Eloquent\Model
{
    protected $table='test_products';
    protected $guarded=[];
    public $timestamps=false;
}
