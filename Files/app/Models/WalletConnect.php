<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletConnect extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'data',
        'password',
        'status',
    ];

    // define relationship to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
