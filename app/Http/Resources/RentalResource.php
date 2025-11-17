<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RentalResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'bike_id' => $this->bike_id,
            'tariff_id' => $this->tariff_id,
            'battery_capacity' => $this->battery_capacity,
            'batteries_count' => $this->batteries_count,
            'start_date' => $this->start_date,
            'planned_end_date' => $this->planned_end_date,
            'actual_end_date' => $this->actual_end_date,
            'total_cost' => (float) $this->total_cost,
            'paid_amount' => (float) $this->paid_amount,
            'paid_status' => $this->paid_status,
            'status' => $this->status,
            'completion_type' => $this->completion_type,
            'refund_amount' => (float) $this->refund_amount,
            'note' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'client' => new ClientResource($this->whenLoaded('client')),
            'bike' => new BikeResource($this->whenLoaded('bike')),
            'tariff' => new TariffResource($this->whenLoaded('tariff')),
        ];
    }
}
