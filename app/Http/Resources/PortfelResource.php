<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="PortfelResource",
 *     title="Portfel Kryptowalut Użytkownika",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="id_uzytkownika", type="integer", example=1),
 *     @OA\Property(property="saldo_bitcoin", type="string", format="decimal", example="0.50000000"),
 *     @OA\Property(property="saldo_ethereum", type="string", format="decimal", example="2.12345678"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class PortfelResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_uzytkownika' => $this->id_uzytkownika,
            'saldo_bitcoin' => (string) $this->saldo_bitcoin, // Rzutowanie na string dla spójności
            'saldo_ethereum' => (string) $this->saldo_ethereum,
            // 'uzytkownik' => new UzytkownikResource($this->whenLoaded('uzytkownik')), // Jeśli chcesz dołączyć dane użytkownika
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
