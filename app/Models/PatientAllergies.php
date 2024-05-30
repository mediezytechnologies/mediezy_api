<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientAllergies extends Model
{
    use HasFactory;
    protected $table = "patient_allergies";
    protected $fillable = ['allergy_id', 'patient_id', 'allergy_details'];
}
