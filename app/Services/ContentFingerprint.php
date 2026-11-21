<?php

namespace App\\Services;

use App\\Models\\SocialPost;

final class ContentFingerprint
{
    public function for(SocialPost $post): string
    {
        $snapshot = [
            'version' => $post->version,
            'title' => $post->title,
            'body' => $post->body,
            'media_reference' => $post->media_reference,
            'channels' => collect($post->channels ?? [])->sort()->values()->all(),
            'scheduled_at' => $post->scheduled_at?->toISOString(),
        ];

        return hash(
            'sha256',
            json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );
    }
}