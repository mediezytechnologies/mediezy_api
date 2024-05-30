<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorRegisterAttempt extends Model
{
    use HasFactory;
    protected $table = 'doctor_register_attempts';

    protected $fillable = [
        'name',
        'email',
        'mobile_number'
    ];
}
