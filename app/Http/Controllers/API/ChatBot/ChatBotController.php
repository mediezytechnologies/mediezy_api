<?php

namespace App\Http\Controllers\API\ChatBot;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserChatQuery;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Web\WebDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatBotController extends Controller
{

    public function handle(Request $request)
    {

        DriverManager::loadDriver(WebDriver::class);

        $config = [];

        $botman = BotManFactory::create($config);

        $botman->hears('hello', function (BotMan $bot) {
            $bot->reply('Hello! Welcome to Mediezy');
        });
        $botman->fallback(function (BotMan $bot) {
            $bot->reply('Sorry, I did not understand that.');
        });

        $botman->listen();
    }

    // public function handle(Request $request)
    // {


    //     DriverManager::loadDriver(WebDriver::class);

    //     $config = [];

    //     $botman = BotManFactory::create($config);


    //     $botman->hears('hello', function (BotMan $bot) {
    //         $bot->reply('Hello!');
    //     });
    //     $botman->fallback(function (BotMan $bot) {
    //         $bot->reply('Sorry, I did not understand that.');
    //     });

    //     $botman->listen();
    // }

    public function saveUserChat(Request $request)
    {
        $rules = [
            'user_id' => 'required|int',
            'message' => 'required|string',
        ];

        $messages = [
            'user_id.required' => 'User ID is required.',
            'user_id.int' => 'User ID must be an integer.',
            'message.required' => 'Please enter your query.',
            'message.string' => 'Message must be a string.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $chat = new UserChatQuery();
            $chat->user_id = $request->user_id;
            $chat->message = $request->message;
            $chat->save();

            $user_name = User::where('id', $request->user_id)->pluck('firstname')->first();

            return response()->json([
                'success' => true,
                'message' => "Thank you for your message $user_name. We will review your query and get back to you shortly."
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'We couldn\'t process your request at the moment. Please try again in a few minutes.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
