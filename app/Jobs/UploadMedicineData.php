<?php

namespace App\Jobs;

use App\Models\MedicineBase;
use App\Models\MedicineUploadLogs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadMedicineData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $queue_arr;

    /**
     * Create a new job instance.
     */
    public function __construct(array $queue_arr)
    {
        $this->queue_arr = $queue_arr;
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $id  = $this->queue_arr['id'];

        $med_upload_log   = MedicineUploadLogs::where('id', $id)->first();

        if (!$med_upload_log) {
            Log::channel('medicine_upload_logs')->error("Medicine upload log not found for ID: $id");
            return;
        }


        $filename = Storage::disk('public')->path($med_upload_log->upload_file_path);

        Log::channel('medicine_upload_logs')->info("Processing file - " . $filename);

        if (!file_exists($filename) || !is_readable($filename)) {
            Log::channel('medicine_upload_logs')->info("Not Readable");
        }
        $inc            = 0;
        $total_data     = 0;
        $header         = null;
        $insertArray    = array();
        $delimiter      = ',';

        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                $row = array_map('trim', $row);
                if (!$header) {

                    $header = $row;
                } else {

                    if (count($row) < 9) {
                        Log::channel('medicine_upload_logs')->info("Skipping incomplete row");
                        continue;
                    }

                    $insertArray[$inc] = [
                        'medicine_name'         => $row[0] ?? NULL,
                        'manufacturers'         => $row[1] ?? NULL,
                        'salt_composition'      => $row[2] ?? NULL,
                        'description'           => $row[3] ?? NULL,
                        'packaging'             => $row[4] ?? NULL,
                        'mrp'                   => $row[5] ?? NULL,
                        'primary_use'           => $row[6] ?? NULL,
                        'storage'               => $row[7] ?? NULL,
                        'common_side_effect'    => $row[8] ?? NULL
                    ];

                    if ($inc >= 100) {
                        DB::table('medicine_base')->insert($insertArray);
                        $insertArray = [];
                        $inc = 0;
                    }
                    $inc++;
                    $total_data++;
                }
            }

            if ($inc != 0) {
                DB::table('medicine_base')->insert($insertArray);
                $insertArray = [];
            }
            fclose($handle);

            $med_upload_log->status      = "1";
            $med_upload_log->entry_count = $total_data;
            $med_upload_log->save();

            Log::channel('medicine_upload_logs')->info("Medicine upload Processing Completed");
        } else {
            Log::channel('medicine_upload_logs')->info("Failed to open file");
        }
    }


}
