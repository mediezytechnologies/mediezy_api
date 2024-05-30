<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SymptomQuestion extends Model
{
    use HasFactory;
    protected $table = "symptom_questions";
    protected $fillable = [
        'id',
        'symptom_id',
        'symptom_question',
        'symptom_question_image',
        'option_1', 'option_2',
        'option_3', 'option_4',
        'option_5'
    ];
}
