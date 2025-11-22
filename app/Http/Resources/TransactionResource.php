<?php
// app/Http/Resources/TransactionResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'client_id' => $this->client_id,
            'bank_transaction_id' => $this->bank_transaction_id,
            'qr_code_id' => $this->qr_code_id,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'status_ru' => $this->getStatusInRussian(),
            'type' => $this->type,
            'type_ru' => $this->getTypeInRussian(),
            'description' => $this->description,
            'qr_code_url' => $this->qr_code_url,
            'expires_at' => $this->expires_at,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'client' => new ClientResource($this->whenLoaded('client')),

            // Дополнительные поля
            'is_expired' => $this->isExpired(),
            'remaining_time' => $this->expires_at?->diffForHumans(),
        ];
    }

    private function getStatusInRussian(): string
    {
        // ДОБАВЬТЕ ПРОВЕРКУ НА NULL
        if (is_null($this->status)) {
            return 'Неизвестен';
        }

        return match($this->status) {
            'pending' => 'Ожидает оплаты',
            'processing' => 'В обработке',
            'completed' => 'Завершена',
            'failed' => 'Неуспешная',
            'expired' => 'Просрочена',
            'cancelled' => 'Отменена',
            default => $this->status,
        };
    }

    private function getTypeInRussian(): string
    {
        // ДОБАВЬТЕ ПРОВЕРКУ НА NULL
        if (is_null($this->type)) {
            return 'Неизвестен';
        }

        return match($this->type) {
            'payment' => 'Платеж',
            'refund' => 'Возврат',
            default => $this->type,
        };
    }
}
