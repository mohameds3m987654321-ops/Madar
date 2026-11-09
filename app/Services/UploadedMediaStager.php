<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class UploadedMediaStager
{
    public function stage(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $path = $file->store('pending/' . $directory, $disk);

        if ($path === false) {
            throw new RuntimeException('Unable to store the uploaded media file.');
        }

        return $path;
    }
}
