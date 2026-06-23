<?php

namespace App\Http\Controllers;

use App\Services\SepayWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SepayWebhookController extends Controller
{
    public function handle(Request $request, SepayWebhookService $service): JsonResponse
    {
        $service->receive($request);

        return response()->json([
            'success' => true,
        ]);
    }
}
