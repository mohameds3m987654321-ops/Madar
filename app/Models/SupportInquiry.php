<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportInquiry extends Model
{
    protected $fillable = [
        'question',
        'status',
        'conversation',
        'access_token_hash',
        'contact_name',
        'contact_email',
        'contact_phone',
    ];

    protected function casts(): array
    {
        return [
            'conversation' => 'array',
        ];
    }
}
