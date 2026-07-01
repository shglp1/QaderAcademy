<?php

namespace App\Http\Requests\Trainer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChapterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole(['trainer', 'admin', 'super_admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title_en' => 'sometimes|required|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'description_en' => 'nullable|string|max:2000',
            'description_ar' => 'nullable|string|max:2000',
            'order' => 'sometimes|integer|min:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title_en.required' => __('messages.chapter_title_required'),
            'title_en.max' => __('messages.chapter_title_max'),
            'order.integer' => __('messages.order_must_be_integer'),
            'order.min' => __('messages.order_min'),
        ];
    }
}
