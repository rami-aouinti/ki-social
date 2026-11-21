<?php
use App\\Services\\WeeklyPlanningService;
use Illuminate\\Support\\Facades\\Artisan;
use Illuminate\\Support\\Facades\\File;
Artisan::command('social:plan',function(WeeklyPlanningService $service){$input=json_decode(File::get(base_path('data/weekly-input.json')),true,flags:JSON_THROW_ON_ERROR);$plan=$service->generate($input);File::ensureDirectoryExists(storage_path('app/results'));File::put(storage_path('app/results/weekly-plan.json'),json_encode($plan,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));File::put(storage_path('app/results/weekly-report.txt'),$service->report($plan));$this->info('Weekly plan and report generated.');})->purpose('Generate the 3+2 weekly plan and capacity report');