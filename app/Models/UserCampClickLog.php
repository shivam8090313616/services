<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCampClickLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'campaign_id',
        'advertiser_code',
        'device_type',
        'device_os',
        'ad_type',
        'country',
        'ip_address',
        'amount',
    ];
    protected $casts = [
        'created_at' => "datetime:d F Y - h:i:s",
    ];
}
