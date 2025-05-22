<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KartaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_konta' => $this->id_konta,
            // Maskowanie numeru karty - pokazujemy tylko ostatnie 4 cyfry
            'nr_karty_masked' => '**** **** **** ' . substr($this->nr_karty, -4),
            'data_waznosci' => $this->data_waznosci->format('Y-m-d'),
            'zablokowana' => $this->zablokowana,
            'limit_dzienny' => $this->whenNotNull($this->limit_dzienny),
            'typ_karty' => $this->whenNotNull($this->typ_karty),
            'platnosci_internetowe_aktywne' => $this->platnosci_internetowe_aktywne,
            'platnosci_zblizeniowe_aktywne' => $this->platnosci_zblizeniowe_aktywne,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'konto' => new KontoLightResource($this->whenLoaded('konto')), // Opcjonalnie, jeśli ładujesz relację
        ];
    }
}
