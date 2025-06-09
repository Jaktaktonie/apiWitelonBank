<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZlecenieStaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_uzytkownika' => $this->id_uzytkownika,
            'id_konta_zrodlowego' => $this->id_konta_zrodlowego,
            'nr_konta_docelowego' => $this->nr_konta_docelowego,
            'nazwa_odbiorcy' => $this->nazwa_odbiorcy,
            'tytul_przelewu' => $this->tytul_przelewu,
            'kwota' => (float)$this->kwota,
            'czestotliwosc' => $this->czestotliwosc,
            'data_startu' => $this->data_startu->format('Y-m-d'),
            'data_nastepnego_wykonania' => $this->data_nastepnego_wykonania ? $this->data_nastepnego_wykonania->format('Y-m-d') : null,
            'data_zakonczenia' => $this->data_zakonczenia ? $this->data_zakonczenia->format('Y-m-d') : null,
            'aktywne' => $this->aktywne,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'konto_zrodlowe' => new KontoLightResource($this->whenLoaded('kontoZrodlowe')),
            'uzytkownik' => new UzytkownikLightResource($this->whenLoaded('uzytkownik')), // Prostsza wersja zasobu użytkownika
        ];
    }
}
