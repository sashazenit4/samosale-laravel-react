<?php
namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_id' => [
                'required',
                'exists:payments,id',
                function ($attribute, $value, $fail) {
                    $payment = Payment::find($value);

                    if (!$payment) {
                        return;
                    }

                    if (!in_array($payment->status, ['partially_paid', 'unpaid'])) {
                        $fail('Транзакция может быть создана только для платежей со статусом "частично оплачен" или "не оплачен".');
                    }

                    $remainingAmount = $payment->total_amount - $payment->paid_amount;
                    if ($remainingAmount <= 0) {
                        $fail('Нельзя создать транзакцию для полностью оплаченного платежа.');
                    }
                }
            ],
            'amount' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    $payment = Payment::find($this->payment_id);

                    if (!$payment) {
                        return;
                    }

                    $maxAmount = $payment->total_amount - $payment->paid_amount;
                    if ($value > $maxAmount) {
                        $fail("Сумма транзакции не может превышать {$maxAmount}.");
                    }
                }
            ],
            'description' => 'nullable|string|max:500',
            'environment' => 'sometimes|in:sandbox,production',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_id.exists' => 'Указанный платеж не существует.',
            'amount.min' => 'Сумма транзакции должна быть не менее 1 рубля.',
        ];
    }
}
