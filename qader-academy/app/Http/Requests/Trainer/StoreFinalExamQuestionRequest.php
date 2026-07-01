<?php

namespace App\Http\Requests\Trainer;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinalExamQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isTrainer();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'final_exam_id' => 'required|exists:final_exams,id',
            'question' => 'required|string|max:2000',
            'question_ar' => 'nullable|string|max:2000',
            'type' => 'required|in:mcq,written',
            'options' => 'nullable|array',
            'options.*' => 'string|max:500',
            'correct_answer' => 'required|string',
            'model_answer' => 'nullable|string|max:5000',
            'points' => 'nullable|numeric|min:1',
        ];
    }
}
