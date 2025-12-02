<?php
// app/Http/Resources/PaymentResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'month' => $this->month,
            'month_ru' => $this->getMonthInRussian(),
            'status' => $this->status,
            'status_ru' => $this->getStatusInRussian(),
            'year' => $this->year,
            'generated_at' => $this->generated_at,
            'paid_at' => $this->paid_at,
            'total_amount' => (float) $this->total_amount,
            'paid_amount' => (float) $this->paid_amount,
            'remaining_amount' => (float) ($this->total_amount - $this->paid_amount),
            'payment_type' => $this->payment_type,
            'payment_type_ru' => $this->getPaymentTypeInRussian(),
            'client_id' => $this->client_id,
            'client' => new ClientResource($this->whenLoaded('client')),
            'article' => $this->article,
            'article_ru' => $this->getArticleInRussian(),
            'purpose' => $this->purpose,
            'rental_id' => $this->rental_id,
            'rental' => $this->whenLoaded('rental'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function getMonthInRussian(): string
    {
        $months = [
            'january' => 'Январь',
            'february' => 'Февраль',
            'march' => 'Март',
            'april' => 'Апрель',
            'may' => 'Май',
            'june' => 'Июнь',
            'july' => 'Июль',
            'august' => 'Август',
            'september' => 'Сентябрь',
            'october' => 'Октябрь',
            'november' => 'Ноябрь',
            'december' => 'Декабрь',
        ];

        return $months[$this->month] ?? $this->month;
    }

    private function getStatusInRussian(): string
    {
        return match($this->status) {
            'paid' => 'Оплачен',
            'partially_paid' => 'Оплачен частично',
            'unpaid' => 'Не оплачен',
            default => $this->status,
        };
    }

    private function getPaymentTypeInRussian(): string
    {
        return match($this->payment_type) {
            'cash' => 'Наличная',
            'cashless' => 'Безналичная',
            'mixed' => 'Смешанная',
            'corporate' => 'Корпоративная',
            default => $this->payment_type,
        };
    }

    private function getArticleInRussian(): string
    {
        return match($this->article) {
            'bike_rental' => 'Аренда велосипеда',
            'bike_repair' => 'Ремонт велосипеда',
            default => $this->article,
        };
    }
}
