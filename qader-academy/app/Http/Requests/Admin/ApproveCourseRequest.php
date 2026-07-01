<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveCourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'super_admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [];
    }
}
