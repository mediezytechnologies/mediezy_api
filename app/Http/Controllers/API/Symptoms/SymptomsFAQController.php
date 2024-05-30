<?php

namespace App\Http\Controllers\API\Symptoms;

use App\Http\Controllers\Controller;
use App\Models\SymptomQuestion;
use App\Models\Symtoms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SymptomsFAQController extends Controller
{
    public function listSymptoms()
    {
        $symptoms = Symtoms::select('id', 'symtoms', 'symptom_image')
            ->whereNotNull('symptom_image')
            ->get();

        $symptomData = [];

        if ($symptoms->isNotEmpty()) {
            foreach ($symptoms as $symptom) {
                $symptomData[] = [
                    'symptom_id' => $symptom->id,
                    'symptom_name' => $symptom->symtoms,
                    'symptom_image' => $symptom->symptom_image ? asset("SymptomImages/{$symptom->symptom_image}") : null
                ];
            }

            return response()->json(['status' => true, 'message' => 'All symptoms fetched', 'data' => $symptomData], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'No symptoms found', 'data' => null], 404);
        }
    }

    public function updateSymptominage(Request $request)
    {

        $symptom_data = Symtoms::where('id', $request->id)->first();

        if ($request->hasFile('image')) {
            $imageFile = $request->file('image');

            if ($imageFile->isValid()) {
                $imageName = $imageFile->getClientOriginalName();
                $imageFile->move(public_path('SymptomImages'), $imageName);
                $symptom_data->symptom_image = $imageName;
                $symptom_data->save();
                return response()->json(['status' => true, 'message' => 'Symptom image updated successfully', 'data' => $symptom_data], 200);
            }
        }
    }

    public function getSymptomsQuestions(Request $request)
    {
        $symptom_data = SymptomQuestion::select(
            'id',
            'symptom_id',
            'symptom_question',
            'symptom_question_image',
            'option_1',
            'option_2',
            'option_3',
            'option_4',
            'option_5'
        )
            ->where('symptom_id', $request->symptom_id)
            ->get()
            ->map(function ($item) {
                $item->options = [
                    $item->option_1,
                    $item->option_2,
                    $item->option_3,
                    $item->option_4,
                    $item->option_5,
                ];

                unset($item->option_1, $item->option_2, $item->option_3, $item->option_4, $item->option_5);

                return $item;
            });

        if ($symptom_data->isNotEmpty()) {
            return response()->json(['status' => true, 'data' => $symptom_data], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'No data found'], 404);
        }
    }

    public function addSymptomsQuestions(Request $request)
    {
        // Validation rules
        $rules = [
            'symptom_id' => 'required|integer',
            'symptom_question' => 'required|string|max:255',
            'symptom_question_image' => 'sometimes|nullable|string|max:255',
            'option_1' => 'required|string|max:100',
            'option_2' => 'required|string|max:100',
            'option_3' => 'required|string|max:100',
            'option_4' => 'required|string|max:100',
            'option_5' => 'required|string|max:100',
        ];

        $messages = [
            'symptom_id.required' => 'The symptom ID field is required.',
            'symptom_question.required' => 'The symptom question field is required.',
            'option_1.required' => 'Option 1 is required.',
            'option_2.required' => 'Option 2 is required.',
            'option_3.required' => 'Option 3 is required.',
            'option_4.required' => 'Option 4 is required.',
            'option_5.required' => 'Option 5 is required.',

        ];
        $validatedData = $request->validate($rules, $messages);
        $symptomQuestion = new SymptomQuestion($validatedData);
        $symptomQuestion->save();

        return response()->json(['status' => true, 'data' => $symptomQuestion], 200);
    }

    public function addSymptomUserResponse(Request $request)
    {
        $data = [
            'question_id' => $request->input('question_id'),
            'selected_option' => $request->input('selected_option'),
        ];

        $rules = [
            'question_id' => 'required|integer',
            'selected_option' => 'required|in:option_1,option_2,option_3,option_4,option_5',
        ];

        $messages = [
            'question_id.required' => 'The question ID field is required.',
            'question_id.integer' => 'The question ID must be an integer.',
            'selected_option.required' => 'You must select an option.',
            'selected_option.in' => 'The selected option is invalid. Please select a valid option.',
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    }
}
