<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];
    
    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menu_product');
    }
}
