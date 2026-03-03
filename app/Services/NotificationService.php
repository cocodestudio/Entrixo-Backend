<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public static function send($userId, $title, $message, $type = 'general')
    {
        DB::table('notifications')->insert([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = DB::table('users')->where('id', $userId)->first();

        if ($user && $user->fcm_token) {
            $messaging = app('firebase.messaging');
            
            $notification = Notification::create($title, $message);
            
            $cloudMessage = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification($notification)
                ->withData(['type' => $type]);

            $messaging->send($cloudMessage);
        }
    }
}