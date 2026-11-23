<?php

namespace Tests\Feature;

use App\Contracts\PublishingGateway;
use App\Models\SocialPost;
use App\Services\ApprovalService;
use App\Services\PublishingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function createPost(array $overrides = []): SocialPost
    {
        return SocialPost::create(array_merge([
            'idea_id' => 'IDEA-001',
            'title' => 'Brillenform finden',
            'body' => 'Lass dich beraten.',
            'media_reference' => 'media/demo.mp4',
            'channels' => ['instagram', 'facebook'],
            'scheduled_at' => now()->addDay(),
            'status' => 'draft',
            'version' => 1,
            'assigned_person' => 'A',
        ], $overrides));
    }

    private function approve(SocialPost $post): void
    {
        $service = app(ApprovalService::class);
        $service->submit($post);
        $service->approve($post, 'Reviewer A');
    }

    public function test_complete_approved_post_is_handed_off_only_once_on_retry(): void
    {
        $post = $this->createPost();
        $this->approve($post);

        $service = app(PublishingService::class);
        $first = $service->handoff($post);
        $second = $service->handoff($post);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('publishing_handoffs', 1);
        $this->assertSame('handed_over', $post->fresh()->status);
    }

    public function test_missing_approval_blocks_handoff_without_creating_a_record(): void
    {
        $this->expectException(RuntimeException::class);

        try {
            app(PublishingService::class)->handoff($this->createPost());
        } finally {
            $this->assertDatabaseCount('publishing_handoffs', 0);
        }
    }

    public function test_missing_media_reference_blocks_submission_for_review(): void
    {
        $post = $this->createPost(['media_reference' => null]);

        try {
            app(ApprovalService::class)->submit($post);
            $this->fail('A post without a media reference must not enter review.');
        } catch (RuntimeException $exception) {
            $this->assertSame('A media reference is required.', $exception->getMessage());
        }

        $this->assertSame('draft', $post->fresh()->status);
        $this->assertDatabaseCount('post_approvals', 0);
        $this->assertDatabaseCount('publishing_handoffs', 0);
    }

    public function test_text_change_after_approval_requires_new_approval(): void
    {
        $post = $this->createPost();
        $this->approve($post);

        $post->update([
            'body' => 'Changed after approval',
            'version' => 2,
            'status' => 'draft',
        ]);

        try {
            app(PublishingService::class)->handoff($post);
            $this->fail('Changed content must not reuse the previous approval.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Approval is missing or stale', $exception->getMessage());
        }

        $this->assertDatabaseCount('publishing_handoffs', 0);
    }

    public function test_rejected_gateway_does_not_mark_post_as_handed_over(): void
    {
        $post = $this->createPost();
        $this->approve($post);

        $gateway = \Mockery::mock(PublishingGateway::class);
        $gateway->shouldReceive('handoff')->once()->andReturn(['accepted' => false]);
        $this->app->instance(PublishingGateway::class, $gateway);

        try {
            app(PublishingService::class)->handoff($post);
            $this->fail('A rejected payload must not be recorded as handed over.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Publishing gateway rejected the payload.', $exception->getMessage());
        }

        $this->assertSame('approved', $post->fresh()->status);
        $this->assertDatabaseCount('publishing_handoffs', 0);
    }
}
