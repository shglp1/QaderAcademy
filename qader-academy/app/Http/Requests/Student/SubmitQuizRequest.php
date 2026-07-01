<?php

namespace App\Http\Requests\Student;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitQuizRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $answers = $this->input('answers');

        if (!is_array($answers)) {
            return;
        }

        $isAssociative = array_keys($answers) !== range(0, count($answers) - 1);

        if (!$isAssociative) {
            return;
        }

        $normalizedAnswers = [];

        foreach ($answers as $questionId => $answer) {
            $normalizedAnswers[] = [
                'question_id' => (int) $questionId,
                'answer' => $answer,
            ];
        }

        $this->merge([
            'answers' => $normalizedAnswers,
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isStudent();
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
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'exists:quiz_questions,id'],
            'answers.*.answer' => ['required', 'string'],
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
            'answers.required' => __('messages.answers_required'),
        ];
    }
}
