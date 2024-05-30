<?php

namespace App\Http\Controllers\API\MedicineBase;

use App\Http\Controllers\Controller;
use App\Models\UserFavMedicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Constraint\IsFalse;

class UserMedicineController extends Controller
{
    public function updateFavoriteMedicines(Request $request)
    {
        $request->validate([
            'medicine_id' => 'required',
        ]);

        try {
            $user_id = Auth::user()->id;
            $fav_med = UserFavMedicine::where('medicine_id', $request->medicine_id)
                ->where('user_id', $user_id)
                ->first();

            if ($fav_med) {
                $fav_med->delete();
                $status = true;
                $response = 'Medicine removed from your favorite medicines.';
            } else {

                $fav_med = new UserFavMedicine();
                $fav_med->medicine_id = $request->medicine_id;
                $fav_med->user_id = $user_id;
                $fav_med->save();
                $status = true;
                $response = 'Medicine added to your favorite medicines.';
            }

            return response()->json([
                'status' => $status,
                'response' => $response,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'response' => 'Internal Server Error',
            ]);
        }
    }
}












///
