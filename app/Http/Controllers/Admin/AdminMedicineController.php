<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Jobs\UploadMedicineData;
use App\Models\MedicineUploadLogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminMedicineController extends Controller
{
    public function medicineAdd()
    {
        return view('admin.Dashboard.pages.medicine.medicine_add');
    }
    // public function uploadMedicineData(Request $request)
    // {

    //     $uploaded_file = $request->file('upload_file');


    //     $file_name = 'Medicine' . date('d_') . time() . '.csv';
    //     $file_path = 'employee_discounts/' . date('Ym/') . $file_name;


    //     Storage::disk('public')->put($file_path, file_get_contents($uploaded_file->getRealPath()));

    //     // Validate CSV content
    //     $stat = $this->validateCSV($uploaded_file);

    //     if ($stat == 0) {
    //         return ResponseHelper::error('Invalid File Format', 400);
    //     }

    //     $csvData = array_map('str_getcsv', file($uploaded_file->getRealPath()));
    //     $entryCount = count($csvData) - 1; // Subtract 1 to exclude the header row


    //     $medicine_data_upload_logs = new MedicineUploadLogs();
    //     $medicine_data_upload_logs->upload_file_name = $file_name;
    //     $medicine_data_upload_logs->upload_file_path = $file_path;
    //     // $medicine_data_upload_logs->user_id = null;
    //     $medicine_data_upload_logs->entry_count = $entryCount;

    //     $medicine_data_upload_logs->save();


    //     $queue_arr['id'] = $medicine_data_upload_logs->upload_log_id;

    //     Log::channel('medicine_upload_logs')->info("MA_upload_medicine_data_queue: {$queue_arr['id']}");

    //     dispatch((new UploadMedicineData($queue_arr))->onQueue('MA_upload_medicine_data_queue'));

    //     return ResponseHelper::success('Medicine uploaded successfully', null);
    // }

    public function uploadMedicineData(Request $request)
    {
        $uploaded_file = $request->file('upload_file');
        $file_name = 'Medicine' . date('d_') . time() . '.csv';
        $file_path = 'employee_discounts/' . date('Ym/') . $file_name;

        Storage::disk('public')->put($file_path, file_get_contents($uploaded_file->getRealPath()));

        if ($this->validateCSV($uploaded_file) == 0) {
            return ResponseHelper::error('Invalid File Format', 400);
        }

        $csvData = array_map('str_getcsv', file($uploaded_file->getRealPath()));
        $entryCount = count($csvData) - 1;

        $medicine_data_upload_logs = new MedicineUploadLogs([
            'upload_file_name' => $file_name,
            'upload_file_path' => $file_path,
            'user_id' => auth()->id(),
            'entry_count' => $entryCount,
        ]);
        $medicine_data_upload_logs->save();

    
        $id = $medicine_data_upload_logs->id;

        if (!$id) {
            Log::error('Upload log ID not found after saving.');
            return ResponseHelper::error('Upload log entry failed', 500);
        }

      
        Log::channel('medicine_upload_logs')->info("MA_upload_medicine_data_queue: {$id}");

      
        dispatch((new UploadMedicineData(['id' => $id]))->onQueue('MA_upload_medicine_data_queue'));

        return ResponseHelper::success('Medicine uploaded successfully', null);
    }


    ///////////////////////////////////////////////////////////////////////
    private function validateCSV($uploaded_file)
    {
        $stat = 0;
        $delimiter = ',';

        if (($handle = fopen($uploaded_file, 'r')) !== false) {
            $row = fgetcsv($handle, 1000, $delimiter);

            if (
                $row[0] != 'Medicine Name' || $row[1] != 'Manufacturers'
                || $row[2] != 'Salt Composition' || $row[3] != 'Description'
                || $row[4] != 'Packing' || $row[5] != 'MRP' || $row[6] != 'Primary use'
                || $row[7] != 'Storage' || $row[8] != 'Common Side Effects'
            ) {
                $stat = 0;
            } else {
                $stat = 1;
            }

            fclose($handle);
        }

        return $stat;
    }
}
