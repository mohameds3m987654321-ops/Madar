<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymobCallbackLog extends Model
{
    protected $fillable = [
        'order_id',
        'transaction_id',
        'integration_id',
        'request_method',
        'ip_address',
        'payload_hash',
        'signature_valid',
        'outcome',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
        ];
    }
}
