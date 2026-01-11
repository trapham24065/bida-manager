<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{

    use HasFactory;

    protected $fillable
        = [
            'game_session_id',
            'product_id',
            'quantity',
            'price',
            'cost',
            'total',
            'tax_rate',
        ];

//    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

}
