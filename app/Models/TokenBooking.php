<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenBooking extends Model
{
    use HasFactory;

    protected $table = "token_booking";
    protected $fillable = [
        'id', 'doctor_id',
        'BookedPerson_id',    'PatientName',
        'gender',
        'age', 'MobileNo',
        'Appoinmentfor_id',    'date',    'TokenNumber',    'TokenTime',
        'Bookingtime',    'Is_checkIn', 'Is_completed', 'Is_canceled',    'whenitstart',
        'whenitcomes', 'regularmedicine',    'amount',
        'paymentmethod', 'created_at', 'updated_at', 'clinic_id', 'EndTokenTime',
        'lab_id', 'labtest', 'medicalshop_id', 'prescription_image', 'ReviewAfter', 'Reviewdate', 'reschedule_type',
        'height', 'weight', 'temperature', 'spo2', 'sys', 'dia', 'heart_rate','temperature_type'
    ];

    public function patientDetails()
    {
        return $this->belongsTo(Patient::class, 'BookedPerson_id', 'UserId');
    }


    public function doctor()
    {
        return $this->belongsTo(Docter::class, 'doctor_id');
    }


    public function medicines()
    {
        return $this->hasMany(Medicine::class, 'token_id', 'id');
    }


    // public function tokenBookings()
    //         {
    //             return $this->hasMany(TokenBooking::class, 'lab_id');
    //         }
    // }
}
