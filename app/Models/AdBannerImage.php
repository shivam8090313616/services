<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdBannerImage extends Model
{
    use HasFactory;
    protected $fillable = [
        'campaign_id',
        'advertiser_code',
        'image_type',
        'image_path',
        
    ];
}
