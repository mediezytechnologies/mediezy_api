<?php

namespace App\Console\Commands;

use App\Helpers\PushNotificationHelper;
use App\Models\NewTokens;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendDailyAppointmentReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-daily-appointment-reminder';

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
        $tokens = NewTokens::select('booked_user_id')
            ->where('token_booking_status', 1)
            ->where('token_scheduled_date', $today)
            ->get();

        if ($tokens->isNotEmpty()) {


            foreach ($tokens as $token) {

                $time = Carbon::parse($token->token_start_time)->format('F j, g:i A');
                $booked_user_id = $token->booked_user_id;
                $key = 'send_eta_push_alert_' . $booked_user_id;
                if (Cache::has($key)) {
                    continue;
                }
                $this->sendPushNotification($booked_user_id, $time);
                Cache::put($key, true, now()->addMinute()->endOfMinute());
            }
        }
    }

    private function sendPushNotification($booked_user_id, $time)
    {
        $title = "Appointment Reminder";
        $message = "Your appointment is scheduled for today at $time. Please be ready.";

        $type = "send-e-t-a-push-alert";

        $userIds = [$booked_user_id];
        $notificationHelper = new PushNotificationHelper();
        $response = $notificationHelper->sendPushNotifications($userIds, $title, $message, $type);
        return $response;
    }
}
