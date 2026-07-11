<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\TaskService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateDailyTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:generate-daily {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera las tareas del día para todos los equipos a partir de sus tareas recurrentes activas';

    public function __construct(private readonly TaskService $taskService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $date = $this->option('date')
            ? CarbonImmutable::parse($this->option('date'))
            : CarbonImmutable::today();

        $start = microtime(true);
        $teamsProcessed = 0;
        $tasksInserted = 0;

        Team::query()->chunkById(100, function ($teams) use (&$teamsProcessed, &$tasksInserted, $date): void {
            foreach ($teams as $team) {
                $tasksInserted += $this->taskService->generateForTeam($team, $date);
                $teamsProcessed++;
            }
        });

        $duration = round(microtime(true) - $start, 2);
        $peakMemoryMb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        Log::channel('daily_generation')->info('Generación diaria de tareas completada', [
            'date' => $date->toDateString(),
            'teams_processed' => $teamsProcessed,
            'tasks_inserted' => $tasksInserted,
            'duration_seconds' => $duration,
            'peak_memory_mb' => $peakMemoryMb,
        ]);

        $this->info("Procesados {$teamsProcessed} equipos, {$tasksInserted} tareas insertadas en {$duration}s.");

        return self::SUCCESS;
    }
}
