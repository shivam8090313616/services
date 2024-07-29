<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountriesIps extends Model
{
    use HasFactory;
     protected $fillable = [
        'ip_addr',
        'country_code',
        'country_name',
        'state',        
    ];
}
