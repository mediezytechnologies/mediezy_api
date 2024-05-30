<?php

namespace App\Http\Controllers\API\MedicineBase;

use App\Http\Controllers\Controller;
use App\Models\Docter;
use App\Models\MedicineBase;
use App\Models\MedicineHistory;
use App\Models\UserFavMedicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MedicineBaseController extends Controller
{
    /// main
    // public function getMedicineBySearch(Request $request)
    // {
    //     $user_id = Auth::user()->id;

    //     if (empty($request->medicine_name)) {
    //         $fav_medicines = UserFavMedicine::where('user_id', $user_id)
    //             ->with(['medicine' => function($query) {
    //                 $query->select('id', 'medicine_name');
    //             }])
    //             ->get()
    //             ->pluck('medicine');

    //         if ($fav_medicines->isNotEmpty()) {
    //             $fav_medicines = $fav_medicines->map(function ($medicine) {
    //                 $medicine['fav_status'] = 1; // Set favorite status to 1 for all favorite medicines
    //                 return $medicine;
    //             });
    //             return response()->json(['success' => true, 'medicines' => $fav_medicines]);
    //         } else {
    //             return response()->json(['success' => false, 'message' => 'No medicines found']);
    //         }
    //     } else {
    //         $medicines = MedicineBase::select('medicine_name', 'id')
    //             ->where('medicine_name', 'like', '%' . $request->medicine_name . '%')
    //             ->take(15)
    //             ->get();

    //         if ($medicines->isNotEmpty()) {
    //             // Fetch ids of all medicines retrieved from the database
    //             $medicineIds = $medicines->pluck('id')->toArray();

    //             // Fetch favorite medicines' ids for the current user
    //             $userFavMedicineIds = UserFavMedicine::where('user_id', $user_id)
    //                 ->whereIn('medicine_id', $medicineIds)
    //                 ->pluck('medicine_id')
    //                 ->toArray();

    //             // Iterate through medicines and set fav_status accordingly
    //             $medicines = $medicines->map(function ($medicine) use ($userFavMedicineIds) {
    //                 $medicine['fav_status'] = in_array($medicine['id'], $userFavMedicineIds) ? 1 : 0;
    //                 return $medicine;
    //             });

    //             return response()->json(['success' => true, 'medicines' => $medicines]);
    //         } else {
    //             return response()->json(['success' => false, 'message' => 'No medicines found']);
    //         }
    //     }
    // }
    /////////latest  code
    public function getMedicineBySearch(Request $request)
    {
        $user_id = Auth::user()->id;
        $search_term = $request->medicine_name;
        $medicine = MedicineBase::where('medicine_name', $search_term)->first();
        $medicine_id = $medicine ? $medicine->id : null;
        //the search term to the medicine_history table if search term is not empty
        if (!empty($search_term)) {
            $existing_history = MedicineHistory::where('doctor_id', $user_id)
                ->where('medicine_id', $medicine_id)
                ->first();
        }
        //favorite medicines' ids for the current user
        $userFavMedicineIds = UserFavMedicine::where('user_id', $user_id)
            ->pluck('medicine_id')
            ->toArray();

        //the medicine history for the current doctor
        $medicine_history_query = MedicineHistory::join('medicine_base', 'medicine_base.id', '=', 'medicine_history.medicine_id')
            ->join('medicalprescription', 'medicalprescription.medicine_id', '=', 'medicine_base.id')
            ->select('medicine_base.id', 'medicine_base.medicine_name')
            ->where('medicalprescription.docter_id', $user_id)
            ->distinct('medicine_base.id')
            ->orderBy('medicalprescription.created_at', 'desc')
            ->take(15);

        if (!empty($search_term)) {
            $medicine_history_query->where('medicine_base.medicine_name', 'like', $search_term . '%');
        }

        $medicine_history = $medicine_history_query->get();
        if (empty($search_term)) {
            // fetch all favorite medicines first
            $fav_medicines = MedicineBase::select('id', 'medicine_name')
                ->whereIn('id', $userFavMedicineIds)
                ->take(15)
                ->get();
            if ($fav_medicines->isNotEmpty()) {
                // set fav_status for favorite medicines
                $fav_medicines = $fav_medicines->map(function ($medicine) {
                    $medicine['fav_status'] = 1;
                    return $medicine;
                });
                return response()->json(['success' => true, 'medicine_history' => $medicine_history, 'medicines' => $fav_medicines]);
            } else {
                $all_medicines = MedicineBase::select('id', 'medicine_name')
                    ->take(15)
                    ->get();
                // fav_status for all medicines
                $all_medicines = $all_medicines->map(function ($medicine) use ($userFavMedicineIds) {
                    $medicine['fav_status'] = in_array($medicine['id'], $userFavMedicineIds) ? 1 : 0;
                    return $medicine;
                });
                return response()->json(['success' => true, 'medicine_history' => $medicine_history, 'medicines' => $all_medicines]);
            }
        } else {
            // favorite medicines that match the search term as medicine_base table
            $favorite_medicines = MedicineBase::select('medicine_name', 'id')
                ->where('medicine_name', 'like', $search_term . '%')
                ->whereIn('id', $userFavMedicineIds)
                ->take(15)
                ->get();
            // non-favorite medicines that match the search term with a count to be select
            $non_favorite_medicines = MedicineBase::select('medicine_name', 'id')
                ->where('medicine_name', 'like', $search_term . '%')
                ->whereNotIn('id', $userFavMedicineIds)
                ->take(15 - $favorite_medicines->count())
                ->get();
            $medicines = $favorite_medicines->merge($non_favorite_medicines);
            // fav_status for all medicine_status
            $medicines = $medicines->map(function ($medicine) use ($userFavMedicineIds) {
                $medicine['fav_status'] = in_array($medicine['id'], $userFavMedicineIds) ? 1 : 0;
                return $medicine;
            });
            if ($medicines->isNotEmpty()) {
                return response()->json(['success' => true, 'medicine_history' => $medicine_history, 'medicines' => $medicines]);
            } else {
                return response()->json(['success' => false, 'message' => 'No medicines found']);
            }
        }
    }

    
    public function deleteMedicineHistory(request $request)
    {
        $rules = [
            'doctor_id'     => 'required',
            'medicine_id'   => 'required',
        ];
        $messages = [
            'doctor_id.required' => 'doctor_id is required',
              'medicine_id.required' => 'medicine_id is required'
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $doctor = Docter::where('UserId', $request->doctor_id)->pluck('id')->first();
            if (!$doctor) {
                return response()->json(['status' => false, 'message' => 'Doctor not found']);
            }
            $medicne = MedicineHistory::where('doctor_id', $request->doctor_id)
                ->where('medicine_id', $request->medicine_id)
                ->first();
            if ($medicne) {
                MedicineHistory::where('doctor_id', $request->doctor_id)
                    ->where('medicine_id', $request->medicine_id)
                    ->delete();
            } else {
                return response()->json(['status' => false, 'message' => "No medicne request found."]);
            }
            return response()->json(['status' => true, 'message' => 'medicne deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => "Internal Server Error"]);
        }
    }

}
