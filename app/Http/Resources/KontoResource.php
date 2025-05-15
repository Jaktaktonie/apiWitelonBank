<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KontoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'numer_konta' => $this->nr_konta, // Możesz zmieniać nazwy kluczy
            'aktualne_saldo' => $this->saldo,
            'waluta' => $this->waluta ?? 'PLN', // Przykład z domyślną wartością
            'limit_przelewu_dzienny' => $this->limit_przelewu,
            'czy_zablokowane' => (bool) $this->zablokowane,
            // 'wlasciciel' => new UzytkownikResource($this->whenLoaded('uzytkownik')), // Jeśli chcesz załączyć dane właściciela
            'utworzono' => $this->created_at->toDateTimeString(),
        ];
    }
}
