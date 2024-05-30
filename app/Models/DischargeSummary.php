<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DischargeSummary extends Model
{
    use HasFactory;
    protected $table = 'discharge_summary';
    protected $fillable = [
        'user_id', 'patient_id', 'document_id', 'test_name', 'date', 'lab_name',
        'file_name', 'doctor_name', 'admitted_for', 'hospital_name', 'notes',
        'created_at', 'updated_at'
    ];
}
