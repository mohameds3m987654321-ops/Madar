<?php

namespace App\Console\Commands;

use App\Models\LessonVideoUpload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupIncompleteLessonUploads extends Command
{
    protected $signature = 'lessons:cleanup-uploads {--hours=24}';

    protected $description = 'Remove expired temporary lesson video uploads';

    public function handle(): int
    {
        $cutoff = now()->subHours(max(1, (int) $this->option('hours')));
        $disk = Storage::disk('private');
        $removed = 0;

        LessonVideoUpload::query()
            ->where('updated_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($uploads) use ($disk, &$removed) {
                foreach ($uploads as $upload) {
                    if ($upload->status !== 'completed') {
                        $disk->delete('pending/videos/' . $upload->token . '.mp4');
                    }

                    $disk->deleteDirectory('pending/lesson-uploads/' . $upload->token);
                    $upload->delete();
                    $removed++;
                }
            });

        $this->info("Removed {$removed} expired lesson video uploads.");

        return self::SUCCESS;
    }
}
