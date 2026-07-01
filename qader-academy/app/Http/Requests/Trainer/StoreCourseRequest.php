<?php

namespace App\Http\Requests\Trainer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
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
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'description_en' => ['required', 'string'],
            'description_ar' => ['required', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0'],
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
            'title_en.required' => __('messages.title_required'),
            'title_ar.required' => __('messages.title_required'),
            'description_en.required' => __('messages.description_required'),
            'description_ar.required' => __('messages.description_required'),
            'category_id.required' => __('messages.category_required'),
            'category_id.exists' => __('messages.category_not_found'),
            'price.required' => __('messages.price_required'),
            'price.numeric' => __('messages.price_numeric'),
        ];
    }
}
