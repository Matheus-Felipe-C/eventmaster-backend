<?php

namespace App\Http\Controllers;

use App\Models\Local;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocalController extends Controller
{
    /**
     * Display a listing of locals.
     */
    public function index(): JsonResponse
    {
        $locals = Local::query()->orderBy('name')->get();

        return response()->json($locals);
    }

    /**
     * Display a specific local.
     */
    public function show(Local $local): JsonResponse
    {
        return response()->json($local);
    }

    /**
     * Store a newly created local.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'number_street' => ['required', 'string', 'max:50'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'max_people' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $local = Local::create($validator->validated());

        return response()->json([
            'message' => __('Local created successfully.'),
            'local' => [
                'id' => $local->id,
                'name' => $local->name,
                'street' => $local->street,
                'number_street' => $local->number_street,
                'neighborhood' => $local->neighborhood,
                'max_people' => $local->max_people,
            ],
        ], 201);
    }
}
