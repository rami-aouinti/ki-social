<?php

namespace App\Services;

use InvalidArgumentException;

final class WeeklyPlanningService
{
    public function generate(array $input): array
    {
        foreach (['week', 'people', 'inspirations'] as $required) {
            if (!isset($input[$required])) {
                throw new InvalidArgumentException("Missing input: {$required}");
            }
        }

        if (count($input['inspirations']) < 3) {
            throw new InvalidArgumentException('At least three inspirations are required.');
        }

        $ideas = [
            [
                'id' => 'IDEA-001',
                'topic' => 'Welche Brillenform passt zu deinem Gesicht?',
                'priority' => 'priority',
                'channels' => ['instagram', 'linkedin'],
                'format' => 'Short video + professional post',
                'owner' => 'A',
                'date' => '2026-11-24',
                'effort' => 55,
                'status' => 'draft',
                'reason' => 'Beratungsnah, visuell und kanalübergreifend sinnvoll.',
            ],
            [
                'id' => 'IDEA-002',
                'topic' => 'Mythos oder Fakt: Bildschirm und Augen',
                'priority' => 'priority',
                'channels' => ['facebook', 'instagram'],
                'format' => 'Carousel',
                'owner' => 'B',
                'date' => '2026-11-25',
                'effort' => 65,
                'status' => 'draft',
                'reason' => 'Beantwortet eine Alltagsfrage; fachliche Aussagen vor Freigabe prüfen.',
            ],
            [
                'id' => 'IDEA-003',
                'topic' => 'Ein Blick hinter die Kulissen der Anpassung',
                'priority' => 'priority',
                'channels' => ['linkedin', 'facebook'],
                'format' => 'Photo story',
                'owner' => 'B',
                'date' => '2026-11-27',
                'effort' => 70,
                'status' => 'draft',
                'reason' => 'Macht persönliche Beratung und Service sichtbar.',
            ],
            [
                'id' => 'IDEA-004',
                'topic' => 'Winterlicht und Sehkomfort',
                'priority' => 'reserve',
                'channels' => ['instagram', 'facebook'],
                'format' => 'Story sequence',
                'owner' => 'A',
                'date' => '2026-11-28',
                'effort' => 30,
                'status' => 'draft',
                'reason' => 'Saisonaler Anlass; bei knapper Kapazität zurückstellen.',
            ],
            [
                'id' => 'IDEA-005',
                'topic' => 'Brillenpflege im Alltag',
                'priority' => 'reserve',
                'channels' => ['tiktok', 'instagram'],
                'format' => 'Short video',
                'owner' => 'B',
                'date' => '2026-11-29',
                'effort' => 45,
                'status' => 'draft',
                'reason' => 'Evergreen-Thema und später nachproduzierbar.',
            ],
        ];

        $capacity = [];
        $warnings = [];

        foreach ($input['people'] as $person => $availableMinutes) {
            $committedIdeas = array_filter(
                $ideas,
                fn (array $idea): bool => $idea['owner'] === $person && $idea['priority'] === 'priority'
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
            'Kalenderwoche: '.$plan['week'],
            'Unternehmen: '.$plan['business'],
            '',
            'BEITRÄGE',
        ];

        foreach ($plan['ideas'] as $idea) {
            $lines[] = sprintf(
                '%s | %s | %s | %s | %s | %d min',
                $idea['id'],
                strtoupper($idea['priority']),
                $idea['topic'],
                implode(',', $idea['channels']),
                $idea['owner'],
                $idea['effort']
            );
        }

        $lines[] = '';
        $lines[] = 'KAPAZITÄT';

        foreach ($plan['capacity'] as $person => $capacity) {
            $lines[] = sprintf(
                '%s: %d/%d min; remaining %d%s',
                $person,
                $capacity['planned_minutes'],
                $capacity['available_minutes'],
                $capacity['remaining_minutes'],
                $capacity['warning'] ? ' — WARNING' : ''
            );
        }

        $lines[] = '';
        $lines[] = 'Inspirationsquellen sind nicht verifiziert; fehlende Kennzahlen bleiben unbekannt.';

        return implode(PHP_EOL, $lines).PHP_EOL;
    }
}