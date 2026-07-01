<?php

namespace App\Http\Requests\Trainer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePayoutRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:bank_transfer,paypal,stc_pay'],
            'account_details' => ['required', 'string', 'max:500'],
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
            'amount.required' => __('messages.amount_required'),
            'amount.numeric' => __('messages.amount_numeric'),
            'amount.min' => __('messages.amount_min'),
            'payment_method.required' => __('messages.payment_method_required'),
            'payment_method.in' => __('messages.payment_method_invalid'),
        ];
    }
}
