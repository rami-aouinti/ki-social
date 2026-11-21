<?php
namespace App\\Services;
use App\\Models\\SocialPost;
final class ContentFingerprint {public function for(SocialPost $p):string{$snapshot=['version'=>$p->version,'title'=>$p->title,'body'=>$p->body,'media_reference'=>$p->media_reference,'channels'=>collect($p->channels??[])->sort()->values()->all(),'scheduled_at'=>$p->scheduled_at?->toISOString()];return hash('sha256',json_encode($snapshot,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE));}}