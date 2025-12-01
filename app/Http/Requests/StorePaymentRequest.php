<?php
// app/Http/Requests/StorePaymentRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => ['required', Rule::in([
                'january', 'february', 'march', 'april', 'may', 'june',
                'july', 'august', 'september', 'october', 'november', 'december'
            ])],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'paid_amount' => ['sometimes', 'numeric', 'min:0'],
            'payment_type' => ['required', Rule::in(['cash', 'cashless', 'mixed', 'corporate'])],
            'client_id' => ['required', 'exists:clients,user_id'],
            'article' => ['required', Rule::in(['bike_rental', 'bike_repair'])],
            'purpose' => ['nullable', 'string', 'max:1000'],
            'rental_id' => ['nullable', 'exists:rentals,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.exists' => 'Выбранный клиент не существует.',
            'rental_id.exists' => 'Выбранная аренда не существует.',
            'year.min' => 'Год должен быть не менее 2000.',
            'year.max' => 'Год должен быть не более 2100.',
        ];
    }
}
