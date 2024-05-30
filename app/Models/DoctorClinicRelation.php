<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorClinicRelation extends Model
{
    use HasFactory;
    protected $table = 'doctor_clinic_relations';

    protected $fillable = [
        'doctor_id',
        'clinic_id',
        'relationship_type',
        'start_date',
        'end_date',
    ];
}
