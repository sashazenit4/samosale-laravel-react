<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TariffResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'program' => $this->program,
            'power' => $this->power,
            'price_month' => (float) $this->price_month,
            'price_week1' => (float) $this->price_week1,
            'price_week2' => (float) $this->price_week2,
            'price_week3' => (float) $this->price_week3,
            'price_week4' => (float) $this->price_week4,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
