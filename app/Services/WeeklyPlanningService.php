<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * @group WeeklyPlanningService
 */
final readonly class WeeklyPlanningService {
    public function generate(array $input): array
    {
        foreach (['week', 'people', 'inspirations', 'themes'] as $required) {
            if (!isset($input[$required])) {
                throw new InvalidArgumentException("Missing input: {$required}");
            }
        }

        if (count($input['inspirations']) < 3) {
            throw new InvalidArgumentException('At least three inspirations are required.');
        }

        if (count($input['themes']) !== 5) {
            throw new InvalidArgumentException('Exactly five themes are required: three priorities and two reserves.');
        }

        foreach ($input['themes'] as $index => $theme) {
            foreach (['id', 'topic', 'priority', 'channels', 'format', 'owner', 'date', 'effort', 'reason'] as $field) {
                if (!array_key_exists($field, $theme)) {
                    throw new InvalidArgumentException("Theme {$index} is missing field: {$field}");
                }
            }

            if (!in_array($theme['priority'], ['priority', 'reserve'], true)) {
                throw new InvalidArgumentException("Theme {$index} has an invalid priority.");
            }

            if (!isset($input['people'][$theme['owner']])) {
                throw new InvalidArgumentException("Theme {$index} has an owner without a capacity entry.");
            }

            if (!is_array($theme['channels']) || $theme['channels'] === []) {
                throw new InvalidArgumentException("Theme {$index} must have at least one channel.");
            }

            if (!is_numeric($theme['effort']) || (int) $theme['effort'] < 0) {
                throw new InvalidArgumentException("Theme {$index} has an invalid effort estimate.");
            }
        }

        $priorityCount = count(array_filter(
            $input['themes'],
            fn (array $theme): bool => $theme['priority'] === 'priority'
        ));
        $reserveCount = count(array_filter(
            $input['themes'],
            fn (array $theme): bool => $theme['priority'] === 'reserve'
        ));

        if ($priorityCount !== 3 || $reserveCount !== 2) {
            throw new InvalidArgumentException('The plan must contain three priorities and two reserves.');
        }

        $ideas = array_map(
            static fn (array $theme): array => [
                'id' => (string) $theme['id'],
                'topic' => (string) $theme['topic'],
                'priority' => $theme['priority'],
                'channels' => array_values($theme['channels']),
                'format' => (string) $theme['format'],
                'owner' => (string) $theme['owner'],
                'date' => (string) $theme['date'],
                'effort' => (int) $theme['effort'],
                'status' => 'draft',
                'reason' => (string) $theme['reason'],
            ],
            $input['themes']
        );

        $capacity = [];
        $warnings = [];

        foreach ($input['people'] as $person => $availableMinutes) {
            $committedIdeas = array_filter(
                $ideas,
                static fn (array $idea): bool => $idea['owner'] === $person && $idea['priority'] === 'priority'
            );

            $plannedMinutes = array_sum(array_column($committedIdeas, 'effort'));
            $remainingMinutes = (int) $availableMinutes - $plannedMinutes;

            $capacity[$person] = [
                'available_minutes' => (int) $availableMinutes,
                'planned_minutes' => $plannedMinutes,
                'remaining_minutes' => $remainingMinutes,
                'warning' => $remainingMinutes < 0,
            ];

            if ($remainingMinutes < 0) {
                $warnings[] = sprintf(
                    'Person %s überschreitet das Wochenbudget um %d Minuten. Eine priorisierte Idee verschieben oder reduzieren; verfügbare Zeit wird nicht stillschweigend erhöht.',
                    $person,
                    abs($remainingMinutes)
                );
            }
        }

        return [
            'week' => $input['week'],
            'business' => $input['business'] ?? 'Optiker Demo',
            'seasonal_occasion' => $input['seasonal_occasion'] ?? null,
            'audiences' => $input['audiences'] ?? [],
            'tone' => $input['tone'] ?? '',
            'ideas' => $ideas,
            'inspirations' => array_map(
                fn (array $inspiration): array => $inspiration + [
                    'trend_status' => 'unverified',
                    'metrics' => null,
                ],
                $input['inspirations']
            ),
            'selection_rationale' => 'Drei Prioritäten verbinden Beratung, Information und Vertrauen. Zwei Reserven schaffen Flexibilität. Inspirationsquellen sind keine Trendnachweise; fehlende Kennzahlen bleiben unbekannt.',
            'capacity' => $capacity,
            'warnings' => $warnings,
            'assumptions' => [
                'Effort includes production, variants, coordination and approval.',
                'Reserve ideas are not included in committed workload.',
                'All dates and source identifiers are synthetic.',
            ],
        ];
    }

    public function report(array $plan): string
    {
        $lines = [
            'KI SOCIAL — WOCHENÜBERSICHT',
            'Kalenderwoche: '.($plan['week'] ?? 'unbekannt'),
            'Unternehmen: '.($plan['business'] ?? 'unbekannt'),
            'Saisonaler Anlass: '.($plan['seasonal_occasion'] ?? 'nicht angegeben'),
            'Zielgruppen: '.implode(', ', $plan['audiences'] ?? []),
            'Ton: '.($plan['tone'] ?? 'nicht angegeben'),
            '',
            'BEITRÄGE',
        ];

        foreach ($plan['ideas'] ?? [] as $idea) {
            $lines[] = sprintf(
                '%s | %s | %s | Datum: %s | Status: %s | Kanäle: %s | Format: %s | Verantwortlich: %s | Aufwand: %d min',
                $idea['id'] ?? 'ohne ID',
                strtoupper($idea['priority'] ?? 'unknown'),
                $idea['topic'] ?? 'ohne Thema',
                $idea['date'] ?? 'offen',
                $idea['status'] ?? 'unbekannt',
                implode(', ', $idea['channels'] ?? []),
                $idea['format'] ?? 'nicht festgelegt',
                $idea['owner'] ?? 'nicht zugewiesen',
                (int) ($idea['effort'] ?? 0)
            );
            if (!empty($idea['reason'])) {
                $lines[] = '  Begründung: '.$idea['reason'];
            }
        }

        $lines[] = '';
        $lines[] = 'KAPAZITÄT';

        foreach ($plan['capacity'] ?? [] as $person => $capacity) {
            $lines[] = sprintf(
                '%s: %d/%d min geplant; %d min verbleibend%s',
                $person,
                $capacity['planned_minutes'],
                $capacity['available_minutes'],
                $capacity['remaining_minutes'],
                $capacity['warning'] ? ' — WARNUNG' : ''
            );
        }

        $lines[] = '';
        $lines[] = 'WARNUNGEN';
        foreach ($plan['warnings'] ?? [] as $warning) {
            $lines[] = '- '.$warning;
        }
        if (empty($plan['warnings'])) {
            $lines[] = '- Keine Kapazitätsüberschreitung bei den priorisierten Ideen.';
        }

        $lines[] = '';
        $lines[] = 'INSPIRATIONEN (NICHT ALS TRENDBELEG VERIFIZIERT)';
        foreach ($plan['inspirations'] ?? [] as $inspiration) {
            $lines[] = sprintf(
                '%s | %s | %s | Quelle: %s | Reichweite: %s | Engagement: %s',
                $inspiration['channel'] ?? 'Kanal unbekannt',
                $inspiration['topic'] ?? 'Thema unbekannt',
                $inspiration['observed_at'] ?? 'Datum unbekannt',
                $inspiration['source_id'] ?? 'ID unbekannt',
                $inspiration['reach'] ?? 'unbekannt',
                $inspiration['engagement'] ?? 'unbekannt'
            );
        }

        $lines[] = '';
        $lines[] = 'Hinweis: Inspirationsquellen sind keine Trendnachweise. Fehlende Kennzahlen bleiben unbekannt.';
        $lines[] = 'Reservethemen sind nicht im fest eingeplanten Wochenaufwand enthalten.';

        return implode(PHP_EOL, $lines).PHP_EOL;
    }
}
