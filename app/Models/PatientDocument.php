<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientDocument extends Model
{
    use HasFactory;


    public function LabReport()
    {
        return $this->hasMany(LabReport::class, 'document_id', 'id')->orderBy('date', 'desc');
    }
    public function PatientPrescription()
    {
        return $this->hasMany(PatientPrescriptions::class, 'document_id', 'id')->orderBy('date', 'desc');
    }
    public function DischargeSummary()
    {
        return $this->hasMany(DischargeSummary::class, 'document_id', 'id')->orderBy('date', 'desc');
    }
    public function ScanReport()
    {
        return $this->hasMany(ScanReport::class, 'document_id', 'id')->orderBy('date', 'desc');
    }
}
