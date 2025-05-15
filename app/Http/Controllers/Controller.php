<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="API WitelonBank",
 *     description="Dokumentacja API dla systemu bankowego WitelonBank. Pamiętaj o autoryzacji (przycisk 'Authorize' na górze) dla chronionych endpointów.",
 *     @OA\Contact(
 *           email="support@witelonbank.com"
 *     ),
 *     @OA\License(
 *         name="MIT License",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Główny serwer API"
 * )
 *
 * @OA\Components(
 *     @OA\SecurityScheme(
 *         securityScheme="bearerAuth",
 *         type="http",
 *         scheme="bearer",
 *         bearerFormat="JWT",
 *         description="Wprowadź token JWT z prefiksem Bearer (np. 'Bearer eyJhbGciOiJIUzI1NiI...). Token uzyskasz po zalogowaniu."
 *     ),
 *     @OA\Schema(
 *         schema="ErrorValidation",
 *         type="object",
 *         title="Błąd Walidacji",
 *         properties={
 *             @OA\Property(property="message", type="string", example="Podane dane są nieprawidłowe."),
 *             @OA\Property(property="errors", type="object",
 *                 @OA\AdditionalProperties(
 *                     type="array",
 *
 * @OA\Items(type="string", example="Pole email jest wymagane.")
 *                 )
 *             )
 *         }
 *     ),
 *     @OA\Schema(
 *         schema="ErrorGeneral",
 *         type="object",
 *         title="Błąd Ogólny",
 *         properties={
 *             @OA\Property(property="message", type="string", example="Wystąpił błąd.")
 *         }
 *     )
 * )
 *
 * @OA\SecurityRequirement(
 *   name="bearerAuth"
 * )
 * // Globalne wymaganie bezpieczeństwa - nie zawsze jest potrzebne globalnie,
 * // lepiej definiować je per endpoint, ale jeśli większość jest chroniona, można tu.
 * // Jeśli nie chcesz globalnie, usuń powyższe @OA\SecurityRequirement i dodawaj je
 * // bezpośrednio w adnotacjach konkretnych metod kontrolera, które wymagają autoryzacji:
 * // security={{"bearerAuth":{}}}
 */
abstract class Controller
{
    //
}
