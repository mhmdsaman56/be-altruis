<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    //
    protected $fillable = [
        'user_id',
        'type',
        'interaction_type',
        'reaction_type',
        'content_id',
        'data',
        'read_at',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];
    protected $appends = ['created_at_formatted'];

public function getCreatedAtFormattedAttribute()
{
    return $this->created_at->format('d M Y H:i');
}

}
