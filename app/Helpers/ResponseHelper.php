<?php

namespace App\Helpers;

class ResponseHelper
{

    static function success($message, $data = [], $status = 200)
    {

        $result_array               = array();
        $result_array['status']     = $status;
        $result_array['message']    = $message;
        $result_array['data']       = $data;

        return response()->json($result_array);
    }

    static function error($message, $status = 500, $data = [])
    {

        $result_array               = array();
        $result_array['error']      = true;
        $result_array['status']     = $status;
        $result_array['message']    = $message;
        $result_array['data']       = $data;

        return response()->json($result_array, 400);
    }
}
