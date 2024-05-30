<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RescheduleTokens extends Model
{
    use HasFactory;
    protected $table = 'rescheduled_tokens';

    protected $fillable = [
        'reschedule_type',
        'doctor_id',
        'patient_id',
        'clinic_id',
        'token_number',
        'schedule_id',
        'token_schedule_date',
        'booked_user_id'
    ];
}
