<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'interaction_type',
        'reaction_type',
        'data',
        'content_id',
    ];
    public function content()
    {
        return $this->belongsTo(Content::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'data' => 'array',
    ];
}
