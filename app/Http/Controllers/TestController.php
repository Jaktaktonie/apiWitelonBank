<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'API działa prawidłowo!',
        ], 200);
    }
}
