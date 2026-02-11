<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'content_type', // post, answer, comment, article
        'body',
        'parent_id', // for nested comments or answers
        'slug',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    public function children( )
    {
        return $this->hasMany(Content::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Content::class, 'parent_id');
    }

}
