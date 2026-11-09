<?php

namespace App\Http\Controllers;

use App\Models\CourseCertificate;
use Illuminate\View\View;

class CertificateVerificationController extends Controller
{
    public function show(string $certificateNumber): View
    {
        $certificate = CourseCertificate::query()
            ->with(['course.teacher', 'user'])
            ->where('certificate_number', $certificateNumber)
            ->first();

        return view('Certificates.verify', compact('certificate'));
    }
}
