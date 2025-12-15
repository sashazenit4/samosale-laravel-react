<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Tariff;

class TariffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tariffId = $this->route('tariff')?->id;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'program' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'power' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'in:15,22,43,45'],
            'price_month' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'price_week1' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'price_week2' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'price_week3' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'price_week4' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'price_month.required' => 'Цена за месяц обязательна',
            'price_week1.required' => 'Цена за 1 неделю обязательна',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->program && $this->power) {
                $exists = Tariff::where('program', $this->program)
                    ->where('power', $this->power)
                    ->when($this->route('tariff'), function ($query, $tariff) {
                        $query->where('id', '!=', $tariff->id);
                    })
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'program',
                        'Тариф с такой программой и мощностью уже существует'
                    );
                }
            }
        });
    }
}
