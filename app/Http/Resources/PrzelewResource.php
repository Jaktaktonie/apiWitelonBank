<?php
// app/Http/Resources/PrzelewResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrzelewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'id_konta_nadawcy' => $this->id_konta_nadawcy,
            'nr_konta_odbiorcy' => $this->nr_konta_odbiorcy,
            'nazwa_odbiorcy' => $this->nazwa_odbiorcy,
            'adres_odbiorcy_linia1' => $this->adres_odbiorcy_linia1,
            'adres_odbiorcy_linia2' => $this->adres_odbiorcy_linia2,
            'tytul' => $this->tytul,
            'kwota' => (float) $this->kwota,
            'waluta_przelewu' => $this->waluta_przelewu,
            'status' => $this->status,
            'data_zlecenia' => $this->data_zlecenia->toDateTimeString(),
            'data_realizacji' => $this->data_realizacji ? $this->data_realizacji->toDateTimeString() : null,
            'informacja_zwrotna' => $this->informacja_zwrotna,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];

        // Dodajemy nowe pole, jeśli zostało ustawione w kontrolerze
        // Używamy $this->resource->nazwa_pola, gdy pole zostało dodane dynamicznie do modelu
        if (isset($this->resource->typ_dla_konta_kontekstowego)) {
            $data['typ'] = $this->resource->typ_dla_konta_kontekstowego;
        }
        // Alternatywnie, jeśli zawsze chcesz to pole, nawet jako null:
        // $data['typ_dla_konta_kontekstowego'] = $this->resource->typ_dla_konta_kontekstowego ?? null;

        return $data;
    }
}
