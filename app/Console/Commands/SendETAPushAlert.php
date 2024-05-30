<?php

namespace App\Console\Commands;

use App\Helpers\PushNotificationHelper;
use App\Models\NewTokens;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendETAPushAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-e-t-a-push-alert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send ETA Push Notification';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $now = Carbon::now();
        $current_time = $now->format('H:i');
        $current_date = $now->format('Y-m-d');
        $tokens = NewTokens::select('booked_user_id', 'estimate_checkin_time')
            ->where('token_booking_status', 1)
            ->where('token_scheduled_date', $current_date)
            ->get();

        if ($tokens->isNotEmpty()) {

            foreach ($tokens as $token) {

                $estimate_time = Carbon::parse($token->estimate_checkin_time)->format('H:i');
                $booked_user_id = $token->booked_user_id;
                $key = 'send_eta_push_alert_' . now()->format('YmdHi');

                if ($estimate_time == $current_time) {

                    if (Cache::has($key)) {
                        return;
                    }

                    $this->sendPushNotification($booked_user_id);
                    Cache::put($key, true, now()->addMinute()->endOfMinute());
                }
            }
        } else {
        }
    }

    private function sendPushNotification($booked_user_id)
    {
        $userId = $booked_user_id;
        $userIds = [$userId];
        $title = "Appointment Incoming";
        $message = "You have an appointment scheduled in 20 minutes. Please be ready";
        $type = "send-e-t-a-push-alert";

        // $type - ''
        $notificationHelper = new PushNotificationHelper();

        $response = $notificationHelper->sendPushNotifications($userIds, $title, $message, $type);
        return true;
    }
}
