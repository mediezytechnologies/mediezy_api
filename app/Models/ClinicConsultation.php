<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicConsultation extends Model
{
    use HasFactory;
    protected $table = 'clinic_consultation';
    protected $fillable = [
        'doctor_id',
        'clinic_id',
        'consultation_fee',
        'created_at',
        'updated_at'
    ];


}
