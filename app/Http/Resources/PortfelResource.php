<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfelResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_uzytkownika' => $this->id_uzytkownika,
            'saldo_bitcoin' => (string)$this->saldo_bitcoin,
            'saldo_ethereum' => (string)$this->saldo_ethereum,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
