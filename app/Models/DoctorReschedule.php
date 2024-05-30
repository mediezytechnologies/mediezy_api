<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorReschedule extends Model
{
    use HasFactory;
    protected $table = 'doctor_reschedules';
    protected $fillable = [
        'reschedule_id',
        'reschedule_type',
        'reschedule_duration',
        'doctor_id',
        'clinic_id',
        'reschedule_end_datetime',
        'reschedule_start_datetime'
    ];
}
