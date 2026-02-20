<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('user.{id}', function ($user, $id) {
       logger([
        'auth_user_id' => $user?->id,
        'channel_id' => $id,
    ]);

    return (int) $user->id === (int) $id;
});

