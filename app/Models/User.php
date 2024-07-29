<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uid',
        'auth_provider',
        'user_name',
        'first_name',
        'last_name',
        'email',
        'phonecode',
        'phone',
        'password',
        'login_token',
        'whatsapp_no',
        'website_category',
        'skype_no',
        'gender',
        'dob',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'country',
        'postal',
        'status',
        'user_type',
        'account_type',
        'ac_verified',
        'verify_code',
        'ip',
        'last_login',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'pub_wallet' => 'float',
    ];

    
}
