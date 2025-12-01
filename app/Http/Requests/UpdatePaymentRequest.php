<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => ['sometimes', Rule::in([
                'january', 'february', 'march', 'april', 'may', 'june',
                'july', 'august', 'september', 'october', 'november', 'december'
            ])],
            'year' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            'total_amount' => ['sometimes', 'numeric', 'min:0'],
            'paid_amount' => ['sometimes', 'numeric', 'min:0'],
            'payment_type' => ['sometimes', Rule::in(['cash', 'cashless', 'mixed', 'corporate'])],
            'client_id' => ['sometimes', 'exists:clients,user_id'],
            'article' => ['sometimes', Rule::in(['bike_rental', 'bike_repair'])],
            'purpose' => ['nullable', 'string', 'max:1000'],
            'rental_id' => ['nullable', 'exists:rentals,id'],
        ];
    }
}
