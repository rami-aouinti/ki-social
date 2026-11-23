<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One-to-one relationship between a social post and its approval.
 */
class PostApproval extends Model
{
    protected $fillable = [
        'social_post_id',
        'version',
        'fingerprint',
        'approved_by',
        'approved_at'
    ];

    protected function casts():array
    {
        return [
            'approved_at'=>'datetime'
        ];
    }
}