<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionLog extends Model
{
    use HasFactory;
    protected $fillable = ['transaction_id', 'advertiser_code', 'amount', 'pay_type', 'remark'];
}
