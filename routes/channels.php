<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.AdminUser.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
