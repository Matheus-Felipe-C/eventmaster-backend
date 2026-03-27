<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BatchController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id_event' => ['required', 'integer', 'exists:events,id'],
                'price' => ['required', 'numeric', 'min:0'],
                'initial_date' => ['required', 'date', 'after_or_equal:today'],
                'end_date' => ['required', 'date', 'after:initial_date'],
                'quantity' => ['required', 'integer', 'min:1'],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $batch = Batch::create($validator->validated());

        return response()->json([
            'message' => 'Lote criado com sucesso!',
            'data' => $batch
        ], 201);
    }
}
