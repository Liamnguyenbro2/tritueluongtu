<?php

namespace App\Http\Controllers;

use App\Services\SepayWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SepayWebhookController extends Controller
{
    public function handle(Request $request, SepayWebhookService $service): JsonResponse
    {
        if (! $service->isAuthorized($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized webhook.',
            ], 403);
        }

        $service->receive($request);

        return response()->json([
            'success' => true,
        ]);
    }
}
