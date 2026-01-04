<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerRank extends Model
{

    protected $fillable = ['name', 'min_spending', 'discount_percent', 'color'];

}
