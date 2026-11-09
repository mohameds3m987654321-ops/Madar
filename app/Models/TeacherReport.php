<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherReport extends Model
{
    protected $fillable = [
        'teacher_id',
        'year',
        'month',
        'status',
        'data',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
