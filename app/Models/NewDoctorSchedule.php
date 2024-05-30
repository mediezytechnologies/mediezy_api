<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewDoctorSchedule extends Model
{
    use HasFactory;
    protected $table="new_doctor_schedules";
    protected $fillable = [
        'doctor_id',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'each_token_duration',
        'schedule_type',
        'token_up_to',
        'clinic_id',
    ];

}
