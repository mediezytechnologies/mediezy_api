<?php

namespace App\Console\Commands;

use App\Models\CompletedAppointments;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Helpers\PushNotificationHelper;
use Illuminate\Support\Facades\Log;

class SendCompletedNotificationAtNight extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-completed-notification-at-night';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $today = Carbon::now()->format('Y-m-d');
        // Log::info("running app:send-e-t-a-push-alert command for date: $today");

        $tokens = CompletedAppointments::select('booked_user_id')
            //  ->where('feedback_status', 0)
            ->where('date', $today)
            ->get();


        if ($tokens->isNotEmpty()) {
            // Log::info("found " . $tokens->count() . " appointments for today.");

            foreach ($tokens as $token) {
                // try {
                $time = Carbon::parse($token->token_start_time)->format('F j, g:i A');
                $booked_user_id = $token->booked_user_id;
                $rem = 'send_eta_push_alert_' . $booked_user_id;

                if (Cache::has($rem)) {
                    Log::info("Notification for user $booked_user_id already sent, skipping.");
                    continue;
                }
                $this->sendPushNotification($booked_user_id, $time);
                Cache::put($rem, true, now()->addMinute()->endOfMinute());
                //  Log::info("notification sent and cache set for user $booked_user_id.");
                // } catch (\Exception $e) {
                //   //  Log::error("error sending notification for user $booked_user_id: " . $e->getMessage());
                // }
            }
        }
    }

    private function sendPushNotification($booked_user_id, $time)
    {
        $title = "Appointment Reminder";
        $message = "Your appointment is completed for today at $time.";

        $type = "send-e-t-a-push-alert";
        $userIds = [$booked_user_id];
        $notificationHelper = new PushNotificationHelper();

        // try {
        $response = $notificationHelper->sendPushNotifications($userIds, $title, $message, $type);

        return $response;
        // }
        // catch (\Exception $e) {
        // Log::error("failed to send push notification for user $booked_user_id: " . $e->getMessage());
        // throw $e;
        //}
    }
}
