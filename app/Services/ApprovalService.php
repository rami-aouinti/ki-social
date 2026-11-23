<?php

namespace App\Services;

use App\Models\PostApproval;
use App\Models\SocialPost;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * @group ApprovalService
 */
final readonly class ApprovalService
{
    public function __construct(private ContentFingerprint $fingerprint) {}

    public function submit(SocialPost $post): SocialPost
    {
        if ($post->status !== 'draft') {
            throw new RuntimeException('Only draft content can be submitted for review.');
        }

        $this->assertComplete($post);
        $post->update(['status' => 'pending_review']);

        return $post->refresh();
    }

    public function approve(SocialPost $post, string $reviewer): PostApproval
    {
        return DB::transaction(function () use ($post, $reviewer) {
            $locked = SocialPost::query()->lockForUpdate()->findOrFail($post->id);

            if ($locked->status !== 'pending_review') {
                throw new RuntimeException('Only content pending review can be approved.');
            }

            $this->assertComplete($locked);

            $approval = $locked->approvals()->create([
                'version' => $locked->version,
                'fingerprint' => $this->fingerprint->for($locked),
                'approved_by' => $reviewer,
                'approved_at' => now(),
            ]);

            $locked->update(['status' => 'approved']);

            return $approval;
        });
    }

    private function assertComplete(SocialPost $post): void
    {
        if (blank($post->title) || blank($post->body)) {
            throw new RuntimeException('Title and body are required.');
        }

        if (blank($post->media_reference)) {
            throw new RuntimeException('A media reference is required.');
        }

        if (empty($post->channels)) {
            throw new RuntimeException('At least one channel is required.');
        }

        if (!$post->scheduled_at) {
            throw new RuntimeException('A scheduled publication date is required.');
        }
    }
}