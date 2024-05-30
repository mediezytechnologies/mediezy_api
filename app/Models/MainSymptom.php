<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainSymptom extends Model
{
    use HasFactory;
    protected $table="mainsymptoms";
    protected $fillable = ['Mainsymptoms', 'user_id', 'doctor_id', 'clinic_id','date','TokenNumber'];
}
