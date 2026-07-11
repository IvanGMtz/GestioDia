<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class PhotoService
{
    private const MAX_DIMENSION = 1600;

    private const JPEG_QUALITY = 85;

    private const DEFAULT_RETENTION_DAYS = 90;

    public function store(Team $team, UploadedFile $file): string
    {
        $manager = ImageManager::gd();

        $image = $manager->read($file->getRealPath());
        $image->scaleDown(width: self::MAX_DIMENSION, height: self::MAX_DIMENSION);

        // El driver GD no escribe metadatos EXIF al recodificar, así que los
        // datos de GPS de la foto original se descartan solos (privacidad).
        $encoded = $image->toJpeg(quality: self::JPEG_QUALITY);

        $relativePath = sprintf('photos/%d/%s/%s.jpg', $team->id, now()->format('Y-m'), Str::uuid());

        Storage::disk('public')->put($relativePath, (string) $encoded);

        return $relativePath;
    }

    public function prune(int $olderThanDays = self::DEFAULT_RETENTION_DAYS): int
    {
        $cutoff = now()->subDays($olderThanDays);

        $tasks = Task::withoutGlobalScopes()
            ->whereNotNull('photo_path')
            ->whereNull('photo_pruned_at')
            ->where('completed_at', '<=', $cutoff)
            ->get();

        foreach ($tasks as $task) {
            Storage::disk('public')->delete($task->photo_path);

            $task->update([
                'photo_path' => null,
                'photo_pruned_at' => now(),
            ]);
        }

        return $tasks->count();
    }
}
