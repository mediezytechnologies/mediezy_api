<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorClinicSpecialization extends Model
{
    use HasFactory;
    protected $table = 'doctor_clinic_specialization';
    protected $fillable = [
        'specialization_id',
        'clinic_id',
        'doctor_id',
        
    ];


    
}
