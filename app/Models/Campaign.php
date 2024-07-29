<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;
    protected $fillable = [
        'campaign_id',
        'advertiser_id',
        'advertiser_code',
        'device_type',
        'device_os',
        'campaign_name',
        'campaign_type',
        'ad_type',
        'ad_title',
        'ad_description',
        'target_url',
        'conversion_url',
        'website_category',
        'daily_budget',
        'country_ids',
        'country_name',
        'priority',
        'status',
        'trash',
    ];
}
