<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\Lesson;
use App\Notifications\CourseActivityNotification;
use App\Services\CourseNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use UnexpectedValueException;

class FinalizeMediaUpload implements ShouldQueue
{
    use Queueable;

    public int $tries = 0;

    public int $maxExceptions = 3;

    public array $backoff = [10, 30, 60];

    public int $timeout = 120;

    public function __construct(
        public readonly string $mediaType,
        public readonly int $ownerId,
        public readonly string $temporaryPath,
        public readonly ?string $previousPath = null,
        public readonly bool $notifyPaidStudents = false,
    ) {
        if (!in_array($mediaType, ['course_image', 'lesson_video'], true)) {
            throw new UnexpectedValueException('Unsupported media type.');
        }
    }

    public function middleware(): array
    {
        if ($this->mediaType !== 'lesson_video') {
            return [];
        }

        return [
            (new WithoutOverlapping('lesson-video-finalization'))
                ->shared()
                ->releaseAfter(10)
                ->expireAfter($this->timeout + 30),
        ];
    }

    public function handle(): void
    {
        $disk = Storage::disk($this->mediaType === 'lesson_video' ? 'private' : 'public');
        [$owner, $attribute, $directory] = match ($this->mediaType) {
            'course_image' => [
                Course::find($this->ownerId),
                'image',
                'course_images',
            ],
            'lesson_video' => [
                Lesson::find($this->ownerId),
                'video',
                'videos',
            ],
        };

        if (!$owner) {
            $disk->delete($this->temporaryPath);
            $this->deleteLegacyPublicVideo($this->temporaryPath);
            if ($this->previousPath) {
                $disk->delete($this->previousPath);
                $this->deleteLegacyPublicVideo($this->previousPath);
            }

            return;
        }

        $finalPath = $directory . '/' . basename($this->temporaryPath);
        $currentPath = $owner->getAttribute($attribute);

        if ($currentPath !== $this->temporaryPath && $currentPath !== $finalPath) {
            $disk->delete($this->temporaryPath);
            $this->deleteLegacyPublicVideo($this->temporaryPath);
            if ($this->previousPath && $this->previousPath !== $currentPath) {
                $disk->delete($this->previousPath);
                $this->deleteLegacyPublicVideo($this->previousPath);
            }

            return;
        }

        if (
            $this->mediaType === 'course_image'
            && !in_array(strtolower(pathinfo($this->temporaryPath, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'], true)
        ) {
            $disk->delete($this->temporaryPath);

            throw new UnexpectedValueException('Unsupported course image format.');
        }

        if ($disk->exists($this->temporaryPath)) {
            if (!$disk->move($this->temporaryPath, $finalPath)) {
                throw new RuntimeException('Unable to move the uploaded media file.');
            }
        } elseif (!$disk->exists($finalPath)) {
            throw new RuntimeException('The uploaded media file is missing from storage.');
        }

        if ($currentPath !== $finalPath) {
            $owner->forceFill([$attribute => $finalPath])->save();
        }

        if ($this->previousPath && $this->previousPath !== $finalPath) {
            $disk->delete($this->previousPath);
            $this->deleteLegacyPublicVideo($this->previousPath);
        }

        if ($this->notifyPaidStudents && $owner instanceof Lesson && $owner->course) {
            app(CourseNotificationService::class)->notifyPaidStudents(
                $owner->course,
                new CourseActivityNotification(
                    'lesson_added',
                    'محاضرة جديدة',
                    'تمت إضافة محاضرة جديدة "' . $owner->title . '" إلى كورس ' . $owner->course->title . '.',
                    route('lessons.show', $owner->id, false),
                ),
            );
        }
    }

    private function deleteLegacyPublicVideo(string $path): void
    {
        if ($this->mediaType === 'lesson_video') {
            Storage::disk('public')->delete($path);
        }
    }
}
