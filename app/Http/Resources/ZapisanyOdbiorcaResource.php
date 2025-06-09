<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZapisanyOdbiorcaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nazwa_zdefiniowana' => $this->nazwa_odbiorcy_zdefiniowana,
            'nr_konta' => $this->nr_konta_odbiorcy,
            'rzeczywista_nazwa' => $this->whenNotNull($this->rzeczywista_nazwa_odbiorcy),
            'adres_linia1' => $this->whenNotNull($this->adres_odbiorcy_linia1),
            'adres_linia2' => $this->whenNotNull($this->adres_odbiorcy_linia2),
            'dodano' => $this->created_at->toDateTimeString(),
        ];
    }
}
