<?php

namespace App\Http\Requests\Trainer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
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
            'title_en' => ['sometimes', 'string', 'max:255'],
            'title_ar' => ['sometimes', 'string', 'max:255'],
            'description_en' => ['sometimes', 'string'],
            'description_ar' => ['sometimes', 'string'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'year' => ['nullable', 'integer', 'min:1', 'max:7'],
            'semester' => ['nullable', 'in:first,second'],
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
            'title_en.max' => __('messages.title_max'),
            'title_ar.max' => __('messages.title_max'),
            'category_id.exists' => __('messages.category_not_found'),
            'price.numeric' => __('messages.price_numeric'),
        ];
    }
}
