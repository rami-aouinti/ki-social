<?php
use App\Services\BriefingService;
use App\Services\WeeklyPlanningService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

Artisan::command('social:plan', function (WeeklyPlanningService $service) {
    $input = json_decode(File::get(base_path('data/weekly-input.json')), true, flags: JSON_THROW_ON_ERROR);
    $plan = $service->generate($input);
    File::ensureDirectoryExists(storage_path('app/results'));
    File::put(storage_path('app/results/weekly-plan.json'), json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    File::put(storage_path('app/results/weekly-report.txt'), $service->report($plan));
    $this->info('Weekly plan and report generated.');
    foreach ($plan['warnings'] as $warning) $this->warn($warning);
})->purpose('Generate the 3+2 weekly plan and capacity report');

Artisan::command('social:briefing {ideaId=IDEA-001}', function (string $ideaId, BriefingService $service) {
    $planPath = storage_path('app/results/weekly-plan.json');
    if (!File::exists($planPath)) { $this->error('Run php artisan social:plan first.'); return 1; }
    $plan = json_decode(File::get($planPath), true, flags: JSON_THROW_ON_ERROR);
    $idea = collect($plan['ideas'])->firstWhere('id', $ideaId);
    if (!$idea) { $this->error('Idea not found.'); return 1; }
    $briefing = $service->create($idea);
    File::put(storage_path('app/results/briefing-'.$ideaId.'.json'), json_encode($briefing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $this->info('Briefing generated for '.$ideaId.' (AI response simulated).');
})->purpose('Generate a complete production briefing for one idea');
