<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'user_id' => $this->user_id,
            'telegram_id' => $this->telegram_id,
            'phone_number' => $this->phone_number,
            'name' => $this->name,
            'balance' => (float) $this->balance,
            'referral_code' => $this->referral_code,
        ];
    }
}
