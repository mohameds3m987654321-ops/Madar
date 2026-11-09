<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SecureLessonVideos extends Command
{
    protected $signature = 'media:secure-lesson-videos';

    protected $description = 'Move lesson videos from public storage to private storage.';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('private');
        $moved = 0;

        Lesson::query()
            ->whereNotNull('video')
            ->orderBy('id')
            ->chunkById(100, function ($lessons) use ($public, $private, &$moved): void {
                foreach ($lessons as $lesson) {
                    $path = $lesson->video;
                    $filename = basename($path);

                    if (
                        !in_array(dirname($path), ['videos', 'pending/videos'], true)
                        || preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\z/', $filename) !== 1
                    ) {
                        throw new RuntimeException("Unsupported lesson video path for lesson {$lesson->id}.");
                    }

                    $privatePath = 'videos/' . $filename;

                    if (!$private->exists($privatePath)) {
                        $sourcePath = collect([
                            $path,
                            'videos/' . $filename,
                            'pending/videos/' . $filename,
                        ])->unique()->first(fn(string $candidate): bool => $public->exists($candidate));

                        if ($sourcePath === null) {
                            throw new RuntimeException("Lesson video is missing for lesson {$lesson->id}.");
                        }

                        $stream = $public->readStream($sourcePath);
                        if (!is_resource($stream)) {
                            throw new RuntimeException("Unable to read lesson video for lesson {$lesson->id}.");
                        }

                        try {
                            if (!$private->writeStream($privatePath, $stream)) {
                                throw new RuntimeException("Unable to secure lesson video for lesson {$lesson->id}.");
                            }
                        } finally {
                            fclose($stream);
                        }
                    }

                    if (!$private->exists($privatePath)) {
                        throw new RuntimeException("Private lesson video was not created for lesson {$lesson->id}.");
                    }

                    foreach (['videos/' . $filename, 'pending/videos/' . $filename] as $publicPath) {
                        if ($public->exists($publicPath) && !$public->delete($publicPath)) {
                            throw new RuntimeException("Unable to remove public lesson video for lesson {$lesson->id}.");
                        }
                    }

                    if ($lesson->video !== $privatePath) {
                        $lesson->video = $privatePath;
                        $lesson->save();
                    }

                    $moved++;
                }
            });

        foreach (['videos', 'pending/videos'] as $directory) {
            foreach ($public->allFiles($directory) as $path) {
                if (!$public->delete($path)) {
                    throw new RuntimeException("Unable to remove orphaned public lesson video [{$path}].");
                }
            }
        }

        $this->info("Secured {$moved} lesson video(s).");

        return self::SUCCESS;
    }
}
