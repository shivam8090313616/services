<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignClient extends Model
{
    use HasFactory;
    protected $fillable = [
        'cid',
        'uid',
    ];

}
