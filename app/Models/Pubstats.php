<?php

namespace App\Models\Publisher;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pubstats extends Model
{
    use HasFactory;
    protected $table = 'pub_stats';
    public $timestamps = false;

    protected $fillable = [
        "publisher_code",
        "adunit_id",
        "website_id",
        "device_os",
        "device_type",
        "impressions" ,
        "amount" ,
        "country",
        "udate",
    ];
}
