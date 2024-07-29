<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supports extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid',
        'ticket_no',
        'support_type',
        'subject',
        'message',
        'file',
        'status',
    ];
}
