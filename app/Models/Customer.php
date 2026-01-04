<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{

    protected $fillable = ['name', 'phone', 'email', 'total_spending', 'points', 'note', 'customer_rank_id'];

    public function sessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function rank(): BelongsTo
    {
        return $this->belongsTo(CustomerRank::class, 'customer_rank_id');
    }

}
