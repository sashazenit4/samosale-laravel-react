<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:pending,processing,completed,failed,expired,cancelled',
            'description' => 'sometimes|string|max:500',
            'bank_transaction_id' => 'sometimes|string',
            'qr_code_id' => 'sometimes|string',
        ];
    }
}
