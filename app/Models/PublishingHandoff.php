<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One-to-one relationship between a social post and its handoff.
 */
class PublishingHandoff extends Model {
    protected $fillable = ['social_post_id','idempotency_key','payload','status','handed_off_at'];
    protected function casts():array
    {
        return ['payload'=>'array','handed_off_at'=>'datetime'];
    }
}