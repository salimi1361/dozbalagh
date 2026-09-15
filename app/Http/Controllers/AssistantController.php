<?php

namespace App\Http\Controllers;

use App\Services\Assistant\AssistantContext;
use App\Services\Assistant\AssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function __invoke(Request $request, AssistantService $assistant): JsonResponse
    {
        $validated = $request->validate([
            'message' => [
                'required',
                'string',
                'max:'.(int) config('assistant.max_message_length', 1000),
            ],
        ]);

        $context = AssistantContext::fromRequest($request);

        return response()->json([
            'answer' => $assistant->answer($validated['message'], $context),
        ]);
    }
}
