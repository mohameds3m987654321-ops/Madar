<?php

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['vodafone_cash', 'fawry'])],
            'phone_number' => ['required', 'string', 'max:20', 'regex:/^[0-9+()\-\s]{8,20}$/'],
            'coupon_code' => ['nullable', 'string', 'max:48', 'regex:/^[A-Za-z0-9_-]+$/'],
        ];
    }
}
