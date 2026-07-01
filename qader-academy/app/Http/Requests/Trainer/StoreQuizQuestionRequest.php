<?php

namespace App\Http\Requests\Trainer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuizQuestionRequest extends FormRequest
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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quiz_id' => ['required', 'exists:quizzes,id'],
            'question_text_en' => ['required', 'string'],
            'question_text_ar' => ['required', 'string'],
            'question_type' => ['required', 'in:mcq,written'],
            'options' => ['nullable', 'array', 'required_if:question_type,mcq'],
            'options.*.text_en' => ['required_with:options'],
            'options.*.text_ar' => ['required_with:options'],
            'options.*.is_correct' => ['required_with:options', 'boolean'],
            'correct_answer' => ['nullable', 'string', 'required_if:question_type,written'],
            'points' => ['nullable', 'integer', 'min:1'],
            'order' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quiz_id.required' => __('messages.quiz_required'),
            'quiz_id.exists' => __('messages.quiz_not_found'),
            'question_text_en.required' => __('messages.question_required'),
            'question_text_ar.required' => __('messages.question_required'),
            'question_type.required' => __('messages.question_type_required'),
            'question_type.in' => __('messages.question_type_invalid'),
        ];
    }
}
