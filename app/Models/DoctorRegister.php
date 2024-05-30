<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorRegister extends Model
{
    use HasFactory;

    protected $table = 'doctor_register';
    protected $primaryKey = 'doctor_id ';

    protected $fillable = [
        'first_name',
        'last_name',
        'mobile_number',
        'location',
        'email',
        'hospital_name',
        'specialization',
        'doctor_image',
        'created_at',
        'updated_at',
    ];


}
