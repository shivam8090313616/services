<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'coupon_code',
        'coupon_type',
        'min_bil_amt',
        'coupon_value',
        'max_disc',
        'start_date',
        'end_date',
        'status',
        'trash',
    ];
}
