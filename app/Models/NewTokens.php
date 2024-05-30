<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewTokens extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = "new_tokens";
    protected $primaryKey = 'token_id';
    protected $fillable = [
        'doctor_id',
        'patient_id',
        'clinic_id',
        'schedule_id',
        'token_start_time',
        'token_end_time',
        'token_scheduled_date',
        'actual_token_duration',
        'assigned_token_duration',
        'schedule_type',
        'token_up_to',
        'clinic_id',
        'token_number',
        'checkin_time',
        'checkout_time',
        'is_checkedin',
        'is_checkedout',
        'booked_user_id',
        'doctor_user_id',
        'extra_time_taken',
        'less_time_taken',
        'checkin_difference',
        'break_end_time',
        'break_start_time',
        'is_reserved',
        'reschedule_type',
        'late_checkin_duration',
        'estimate_of_next_token',
        'estimate_checkin_time',
        'doctor_break_time',
        'token_skipped_queue'
    ];
}   
