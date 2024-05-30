<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicineUploadLogs extends Model
{
    use HasFactory;
    protected $table = "medicine_upload_logs";
    protected $fillable = [
        'upload_log_id',
        'entry_count',
        'up_count',
        'user_id',
        'status',
        'upload_file_name',
        'upload_file_path',
        'created_at',
        'updated_at'
    ];
}
