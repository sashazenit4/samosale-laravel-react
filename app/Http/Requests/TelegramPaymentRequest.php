<?php
// app/Http/Requests/TelegramPaymentRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TelegramPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:paid,partially_paid,unpaid',
            'year' => 'sometimes|integer|min:2020|max:2030',
            'month' => 'sometimes|in:january,february,march,april,may,june,july,august,september,october,november,december',
            'payment_type' => 'sometimes|in:cash,cashless,mixed',
            'article' => 'sometimes|in:bike_rental,bike_repair',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'sort_field' => 'sometimes|in:created_at,updated_at,generated_at,paid_at,total_amount',
            'sort_direction' => 'sometimes|in:asc,desc',
        ];
    }

    public function messages(): array
    {
        return [
            'telegram_id.exists' => 'Клиент с указанным Telegram ID не найден.',
            'end_date.after_or_equal' => 'Конечная дата должна быть больше или равна начальной дате.',
        ];
    }
}
