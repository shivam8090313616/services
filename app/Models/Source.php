<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use HasFactory;
      protected $fillable = [
        'uid',
        'title',
        'source_type',
        'status',
        'created_at',
        'updated_at',
    ];
}
