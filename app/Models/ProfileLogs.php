<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileLogs extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid',
        'switcher_login',
        'profile_data',
        'user_type',
        'action'
    ];
}
