<?php

namespace App\Http\Resources;

use App\Models\Konto;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\KontoSimpleResource; // Możemy stworzyć prosty resource dla konta

class PrzelewResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'typ_transakcji' => $this->whenLoaded('kontoNadawcy', function() use ($request) {
                // Określ typ na podstawie tego, czy zalogowany użytkownik jest nadawcą czy odbiorcą
                // To wymaga przekazania ID konta, dla którego jest generowana historia
                // Dla uproszczenia, tutaj można założyć, że 'id_konta_nadawcy' jest sprawdzane.
                // Bardziej zaawansowane określenie typu wymagałoby kontekstu.
                // Jeśli to ID konta, z którego pobieramy historię, jest równe id_konta_nadawcy, to jest to 'wychodzący'
                // Jeśli nr_konta_odbiorcy jest numerem konta, dla którego pobieramy historię, to 'przychodzący'
                // Na razie prostsze rozróżnienie:
                return $this->id_konta_nadawcy == optional(Konto::find(optional($request->route())->parameter('idKonta')))->id ? 'wychodzący' : 'przychodzący';
            }),
            'id_konta_nadawcy' => $this->id_konta_nadawcy,
            // 'konto_nadawcy' => new KontoSimpleResource($this->whenLoaded('kontoNadawcy')), // Jeśli chcesz szczegóły konta nadawcy
            'nr_konta_nadawcy' => optional($this->kontoNadawcy)->nr_konta, // Zakładając relację 'kontoNadawcy' w modelu Przelew
            'nr_konta_odbiorcy' => $this->nr_konta_odbiorcy,
            'nazwa_odbiorcy' => $this->nazwa_odbiorcy,
            'adres_odbiorcy_linia1' => $this->adres_odbiorcy_linia1,
            'adres_odbiorcy_linia2' => $this->adres_odbiorcy_linia2,
            'tytul' => $this->tytul,
            'kwota' => (float) $this->kwota,
            'waluta' => $this->waluta_przelewu,
            'status' => $this->status,
            'data_zlecenia' => $this->data_zlecenia ? $this->data_zlecenia->toIso8601String() : null,
            'data_realizacji' => $this->data_realizacji ? $this->data_realizacji->toIso8601String() : null,
            'informacja_zwrotna' => $this->informacja_zwrotna,
            'utworzono' => $this->created_at->toIso8601String(),
        ];
    }
}
