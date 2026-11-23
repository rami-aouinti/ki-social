<?php

namespace App\Infrastructure\Publishing;

use App\Contracts\PublishingGateway;

/**
 * A deterministic adapter; never calls a social network.
 */
final class FakePublishingGateway implements PublishingGateway {
    public function handoff(array $payload): array {
        return ['accepted'=>true,'simulated'=>true,'gateway'=>'local-json'];
    }
}