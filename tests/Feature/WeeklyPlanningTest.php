<?php

namespace Tests\Feature;

use App\Services\WeeklyPlanningService;
use Tests\TestCase;

class WeeklyPlanningTest extends TestCase
{
    public function test_plan_has_three_priorities_two_reserves_and_capacity_warning(): void
    {
        $input = json_decode(file_get_contents(base_path('data/weekly-input.json')), true);
        $input['people']['B'] = 100;
        $plan = app(WeeklyPlanningService::class)->generate($input);

        $this->assertCount(5, $plan['ideas']);
        $this->assertCount(3, array_filter($plan['ideas'], fn ($idea) => $idea['priority'] === 'priority'));
        $this->assertCount(2, array_filter($plan['ideas'], fn ($idea) => $idea['priority'] === 'reserve'));
        $this->assertTrue($plan['capacity']['B']['warning']);
        $this->assertSame(100, $plan['capacity']['B']['available_minutes']);
        $this->assertSame(135, $plan['capacity']['B']['planned_minutes']);
        $this->assertSame(35, abs($plan['capacity']['B']['remaining_minutes']));
        $this->assertNull($plan['inspirations'][0]['metrics']);
        $this->assertSame('unverified', $plan['inspirations'][0]['trend_status']);
    }

    public function test_topics_are_taken_from_the_structured_input(): void
    {
        $input = json_decode(file_get_contents(base_path('data/weekly-input.json')), true);
        $input['themes'][0]['topic'] = 'A topic supplied by the input';

        $plan = app(WeeklyPlanningService::class)->generate($input);

        $this->assertSame('A topic supplied by the input', $plan['ideas'][0]['topic']);
    }

    public function test_text_report_contains_schedule_status_sources_and_capacity_warnings(): void
    {
        $input = json_decode(file_get_contents(base_path('data/weekly-input.json')), true);
        $plan = app(WeeklyPlanningService::class)->generate($input);
        $report = app(WeeklyPlanningService::class)->report($plan);

        $this->assertStringContainsString('2026-11-24', $report);
        $this->assertStringContainsString('Status: draft', $report);
        $this->assertStringContainsString('SRC-IG-001', $report);
        $this->assertStringContainsString('INSPIRATIONEN (NICHT ALS TRENDBELEG VERIFIZIERT)', $report);
        $this->assertStringContainsString('Reservethemen sind nicht im fest eingeplanten Wochenaufwand enthalten.', $report);
    }
}
