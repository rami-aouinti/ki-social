<?php

namespace App\Contracts;

/**
 * @group PublishingGateway
 */
interface PublishingGateway { public function handoff(array $payload): array; }