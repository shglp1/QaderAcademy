<?php

namespace App\Http\Requests\Trainer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFinalExamQuestionRequest extends FormRequest
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
            'question' => 'nullable|string|max:2000',
            'question_ar' => 'nullable|string|max:2000',
            'type' => 'nullable|in:mcq,written',
            'options' => 'nullable|array',
            'options.*' => 'string|max:500',
            'correct_answer' => 'nullable|string',
            'model_answer' => 'nullable|string|max:5000',
            'points' => 'nullable|numeric|min:1',
        ];
    }
}
