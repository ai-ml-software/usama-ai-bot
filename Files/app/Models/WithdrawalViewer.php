<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalViewer extends Model
{
    protected $fillable = [
        'amount',
        'hash',
        'wallet',
        'timestamp',
        'explorer',
        'code',
        'next_time',
    ];
}
