<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PopupMessage extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'sub_title',
        'image',
        'message',
        'btn_content',
        'btn_link',
        'status',
    ];
}
