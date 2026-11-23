<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a social post in the system.
 */
class SocialPost extends Model {
    protected $fillable = [
        'idea_id',
        'title',
        'body',
        'media_reference',
        'channels',
        'scheduled_at',
        'status',
        'version',
        'assigned_person'
    ];

    protected function casts():array
    {
        return ['channels'=>'array','scheduled_at'=>'datetime','version'=>'integer'];
    }

    public function approvals()
    {
        return $this->hasMany(PostApproval::class);
    }

    public function handoffs()
    {
        return $this->hasMany(PublishingHandoff::class);
    }
}