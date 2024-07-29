<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentClientLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'agent_id',
        'client_id',
    ];

}
