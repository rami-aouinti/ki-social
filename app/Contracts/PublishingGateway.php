<?php
namespace App\Contracts;
interface PublishingGateway { public function handoff(array $payload): array; }