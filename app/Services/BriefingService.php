<?php

namespace App\Services;

use App\Contracts\AiContentGenerator;

/**
 * @group BriefingService
 */
final readonly class BriefingService
{
    public function __construct(private AiContentGenerator $ai) {}

    public function create(array $idea): array
    {
        $generated = $this->ai->generate($idea);

        return [
            'idea_id' => $idea['id'],
            'topic' => $idea['topic'],
            'audience' => 'Menschen, die eine Brille für Alltag und persönlichen Stil suchen.',
            'tone' => 'Persönlich, freundlich, kompetent; keine unbelegten medizinischen Versprechen.',
            'hook' => $generated['hook'],
            'caption' => $generated['caption'],
            'production_shots' => $generated['shots'],
            'text_overlays' => $generated['overlays'],
            'cover_idea' => $generated['cover'],
            'cta' => $generated['cta'],
            'channel_variants' => $generated['variants'],
            'effort_minutes' => [
                'production' => 30,
                'variants' => 10,
                'coordination' => 5,
                'approval' => 10,
            ],
            'ai' => [
                'provider' => $generated['provider'],
                'simulated' => $generated['simulated'],
                'notice' => $generated['notice'],
            ],
        ];
    }
}