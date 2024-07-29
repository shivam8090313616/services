<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PubWebsite extends Model
{
    use HasFactory;
    protected $fillable = [
        'web_name',
        'site_url',
        
    ];
}
