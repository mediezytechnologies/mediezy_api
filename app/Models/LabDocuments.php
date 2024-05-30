<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabDocuments extends Model
{
    use HasFactory;
    protected $table = 'lab_documents';
    protected $fillable = [
        'lab_id',
        'clinic_id',
        'patient_id',
        'doctor_id',
        'token_id',
        'UserId',
        'document_upload',
        'notes',
    ];
}

