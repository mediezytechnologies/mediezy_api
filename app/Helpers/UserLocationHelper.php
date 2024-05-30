<?php

namespace App\Helpers;

use App\Models\UserLocations;

class UserLocationHelper
{
    public static function getUserCurrentLocation($user_id)
    {

        try{
        $current_user_location = UserLocations::where('user_id', $user_id)->latest()->first();

        if ($current_user_location) {
            return $current_user_location;
        } else {
            return null;
        }
        } catch (\Exception $e) {
            return null;
    
    }
}
}