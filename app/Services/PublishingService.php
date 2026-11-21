<?php

namespace App\Services;

use App\Contracts\PublishingGateway;
use App\Models\PublishingHandoff;
use App\Models\SocialPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class PublishingService
{
    public function __construct(
        private readonly ContentFingerprint $fingerprint,
        private readonly PublishingGateway $gateway,
    ) {}

    public function handoff(SocialPost $post): PublishingHandoff
    {
        return DB::transaction(function () use ($post): PublishingHandoff {
            $locked = SocialPost::query()->lockForUpdate()->findOrFail($post->id);
            $approval = $locked->approvals()
                ->where('version', $locked->version)
                ->latest('id')
                ->first();

            if (!$approval || !hash_equals($approval->fingerprint, $this->fingerprint->for($locked))) {
                throw new RuntimeException('Approval is missing or stale; review the current version again.');
            }

            $idempotencyKey = hash('sha256', $locked->id.'|'.$approval->fingerprint);
            $existing = PublishingHandoff::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            // A retry after a successful handoff returns the original record.
            if ($existing) {
                return $existing;
            }

            if ($locked->status !== 'approved') {
                throw new RuntimeException('Explicit approval is required before handoff.');
            }

            if (blank($locked->media_reference)) {
                throw new RuntimeException('Media reference is missing.');
            }

            $payload = [
                'post_id' => $locked->id,
                'idea_id' => $locked->idea_id,
                'version' => $locked->version,
                'channels' => $locked->channels,
                'scheduled_at' => $locked->scheduled_at->toISOString(),
                'text' => $locked->body,
                'media_reference' => $locked->media_reference,
                'approval_fingerprint' => $approval->fingerprint,
                'idempotency_key' => $idempotencyKey,
                'mode' => 'SIMULATED — no external publication',
            ];

            $gatewayResult = $this->gateway->handoff($payload);
            if (!($gatewayResult['accepted'] ?? false)) {
                throw new RuntimeException('Publishing gateway rejected the payload.');
            }

            $record = PublishingHandoff::create([
                'social_post_id' => $locked->id,
                'idempotency_key' => $idempotencyKey,
                'payload' => $payload,
                'status' => 'handed_over',
                'handed_off_at' => now(),
            ]);

            $locked->update(['status' => 'handed_over']);

            File::ensureDirectoryExists(storage_path('app/publishing'));
            File::put(
                storage_path('app/publishing/handoff-'.$record->id.'.json'),
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            return $record;
        });
    }
}