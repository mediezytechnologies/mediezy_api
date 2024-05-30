<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\GeneralCampaignMail;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    public function getAllArticles()
    {
        try {

            $articles = Article::select([
                'articles.article_id as article_id',
                'articles.banner_image',
                'articles.article_title',
                'articles.article_description',
                'articles.author_name',
                'articles.author_image',
                'articles.category_id',
                'articles.created_at',
                'articles.updated_at',
                'categories.category_name',
                'articles.main_banner'
            ])
                ->join('categories', 'categories.id', '=', 'articles.category_id')
                ->get();

            if ($articles->isEmpty()) {
                return response()->json(['status' => false, 'message' => 'No articles found', 'articles' => null]);
            } else {

                $articles = $articles->map(function ($article) {
                    $article->banner_image = $article->banner_image ? asset("{$article->banner_image}") : null;
                    $article->author_image = $article->author_image ? asset("{$article->author_image}") : null;
                    $article->main_banner = $article->main_banner ? asset("{$article->main_banner}") : null;

                    return $article;
                });

                return response()->json(['status' => true, 'message' => 'All articles fetched', 'articles' => $articles]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error retrieving articles', 'error' => $e->getMessage()]);
        }
    }
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function sendNotification(Request $request)
    {

        $rules = [
            'fcm_token' => 'required',
            'title' => 'required',
            'message' => 'required',

        ];
        $messages = [
            'fcm_token.required' => 'fcm_token is required',

        ];

        $validation = Validator::make($request->all(), $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        $firebaseToken = $request->fcm_token;

        $title =  $request->title;

        $message =  $request->message;

        // $SERVER_API_KEY = env('FIREBASE_API_KEY');

        $SERVER_API_KEY = "AAAAM1kjvG0:APA91bHDTAJpPmsVuJfSX01XRdDhCWeq2E5IayBpBcbY_h6dXf5lB7a6-rUWPkEDNSglA1QN1qVkCa5kNXsDp5kjMqEP6aFDTwcQzdxr0gB0xeG_rXetgawE7xZ2fuXYk68njMsxzzaI";

        $data = [
            "registration_ids" => [$firebaseToken],
            "notification" => [
                "title" => $title,
                "body" => $message,
                "content_available" => true,
            ],
            "data" => [
                "title" => $title,
                "body" => $message,
                "type" => "chat",
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

        $response = curl_exec($ch);

        $data = json_decode($response, true);
        return response()->json(['status' => true, 'response' => $data]);
    }

    public function sendMail(Request $request)
    {
        $details = [
            'title' => 'Mail from Your App',
            'body' => 'This is a test email sent from mediezy.'
        ];


        Mail::to('2425ashwee@gmail.com')->send(new GeneralCampaignMail($details));

        return response()->json(['status' => true, 'response' => 'mail send']);
    }
}
