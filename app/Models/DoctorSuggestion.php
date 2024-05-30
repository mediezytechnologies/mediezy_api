<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorSuggestion extends Model
{
    use HasFactory;
    protected $table = 'doctor_suggestions';
    protected $fillable = [
        'user_id',
        'name',
        'mobile_number',
        'place',
        'clinic_name',
        'specialization',
    ];
}
