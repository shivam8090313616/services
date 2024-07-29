<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignLogs extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid',
        'campaign_type',
        'campaign_id',
        'campaign_data',
        'campaign_status',
        'action',
        'user_type'
    ];
}
