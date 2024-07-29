<?php

namespace App\Models\Publisher;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PubDocumentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'doc_type',
        'doc_name',
        'status',
        'remark',
    ];
    protected $table = 'pub_document_logs';
}
