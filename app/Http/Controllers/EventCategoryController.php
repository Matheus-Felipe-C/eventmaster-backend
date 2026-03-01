<?php

namespace App\Http\Controllers;

use App\Models\EventCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventCategoryController extends Controller
{
    /**
     * Store a newly created event category.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $category = EventCategory::create($validator->validated());

        return response()->json([
            'message' => __('Event category created successfully.'),
            'event_category' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
        ], 201);
    }
}
