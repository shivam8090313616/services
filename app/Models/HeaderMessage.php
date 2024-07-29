<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeaderMessage extends Model
{
    use HasFactory;
    protected $fillable = [
        'header_content',
        'slider_content',
        'content_speed',
        'status'
    ];
}
