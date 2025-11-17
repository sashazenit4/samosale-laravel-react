<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BikeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'bike_number' => $this->bike_number,
            'frame_number' => $this->frame_number,
            'status' => $this->status,
            'type' => $this->type,
        ];
    }
}
