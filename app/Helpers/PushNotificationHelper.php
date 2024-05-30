<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class PushNotificationHelper
{

    public function sendPushNotifications(array $user_ids, $title, $message ,$type)
    {
        $firebaseTokens = User::whereIn('id', $user_ids)->pluck('fcm_token')->toArray();
        Log::channel('push_notification_logs')->info('PUSH NOTIFICATION STARTED');
    
        if (!empty($firebaseTokens)) {
            $SERVER_API_KEY = env('FIREBASE_API_KEY');
            Log::channel('push_notification_logs')->info('FCM TOKENS FOUND FOR THE USER IDS: ' . implode(', ', $user_ids));
    
            $data = [
                "registration_ids" => $firebaseTokens,
                "notification" => [
                    "title" => $title,
                    "body" => $message,
                ],
                "data" => [
                    "title" => $title,
                    "body" => $message,
                    "type" => $type,
                ]
            ];
            
            $dataString = json_encode($data);
    
            $headers = [
                'Authorization: key=' . $SERVER_API_KEY,
                'Content-Type: application/json',
            ];
    
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
    
            Log::channel('push_notification_logs')->info('CURL REQUEST INITIATED');
    
            $response = curl_exec($ch);
    
            if (curl_errno($ch)) {
                Log::channel('push_notification_logs')->error('CURL ERROR: ' . curl_error($ch));
            } else {
                Log::channel('push_notification_logs')->info('CURL RESPONSE: ' . $response);
            }
    
            curl_close($ch);
    
            $responseDecoded = json_decode($response, true);
    
            Log::channel('push_notification_logs')->info('DECODED RESPONSE: ' . json_encode($responseDecoded));
    
            return $responseDecoded;
        } else {
            Log::channel('push_notification_logs')->warning('NO FCM TOKENS FOUND FOR USER IDS: ' . implode(', ', $user_ids));
            return null;
        }
    }
    
    
}
