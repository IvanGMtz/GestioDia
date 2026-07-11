<?php

namespace App\Console\Commands;

use App\Services\PhotoService;
use Illuminate\Console\Command;

class PrunePhotos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'photos:prune {--days=90}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina las fotos de evidencia con más de N días desde que se completó la tarea';

    public function __construct(private readonly PhotoService $photoService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $count = $this->photoService->prune($days);

        $this->info("Fotos eliminadas: {$count}");

        return self::SUCCESS;
    }
}
