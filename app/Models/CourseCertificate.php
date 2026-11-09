<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseCertificate extends Model
{
    protected $fillable = [
        'course_id',
        'user_id',
        'certificate_number',
        'issued_at',
    ];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime'];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
