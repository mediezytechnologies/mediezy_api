<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientSymptoms extends Model
{
    use HasFactory;
    protected $table = 'patient_symptoms';

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'specialization_id',
        'symptoms'
    ];
}
