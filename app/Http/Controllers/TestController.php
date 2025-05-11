<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="API Dokumentacja - WitelonBank",
 *      description="Dokumentacja API dla testowego kontrolera",
 *      @OA\Contact(
 *          email="support@witelonbank.com"
 *      )
 * )
 */
class TestController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/test",
     *     summary="Testowy endpoint API",
     *     tags={"Test"},
     *     @OA\Response(
     *         response=200,
     *         description="API działa prawidłowo",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="API działa prawidłowo!")
     *         )
     *     )
     * )
     */
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'API działa prawidłowo!',
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/test",
     *     summary="Testowy POST do API",
     *     tags={"Test"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="param1", type="string", example="Wartość 1"),
     *             @OA\Property(property="param2", type="string", example="Wartość 2")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Pomyślnie utworzono zasób",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="param1", type="string"),
     *             @OA\Property(property="param2", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        return response()->json([
            'param1' => $request->input('param1'),
            'param2' => $request->input('param2')
        ], 201);
    }
}
