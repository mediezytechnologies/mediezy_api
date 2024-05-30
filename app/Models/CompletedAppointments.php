<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompletedAppointments extends Model
{
    use HasFactory;
    protected $table = 'completed_appointments';
    protected $primaryKey = 'appointment_id';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'clinic_id',
        'booked_user_id',
        'appointment_for',
        'symptoms',
        'date',
        'token_number',
        'token_start_time',
        'booking_time',
        'check_in_time',
        'checkout_time',
        'symptom_start_time',
        'symptom_frequency',
        'lab_id',
        'medical_shop_id',
        'prescription_image',
        'schedule_type',
        'height',
        'weight',
        'temperature',
        'spo2',
        'sys',
        'dia',
        'heart_rate',
        'temperature_type',
        'notes',
        'review_after',
        'labtest',
        'created_at',
        'updated_at',
        'new_token_id',
        'scan_id',
        'scan_test'
    ];
}
