<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory;
    protected $fillable = [
        'agent_id',
        'name',
        'email',
        'contact_no',
        'profile_image',
        'skype_id',
        'telegram_id',
    ];

}
