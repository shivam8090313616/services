<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;
    protected $fillable = [
        'advertiser_id',
        'advertiser_code',
        'payment_id',
        'transaction_id',
        'amount',
        'payment_mode',
        'status',
    ];
}
