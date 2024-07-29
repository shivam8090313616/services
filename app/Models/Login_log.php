<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Login_log extends Model
{
    use HasFactory;
    protected $fillable = ['uid', 'browser_name', 'ip_name'];
}
