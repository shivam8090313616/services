<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportType extends Model
{
    use HasFactory;
    protected $fillable = [
        'support_type_name',
        'status',
    ];
}
