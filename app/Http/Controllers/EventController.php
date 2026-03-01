<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    /**
     * List all events.
     */
    public function index(Request $request): JsonResponse
    {
        $events = Event::with(['category', 'local'])
            ->orderBy('date')
            ->orderBy('time')
            ->paginate($request->integer('per_page', 15));

        $events->getCollection()->transform(function (Event $event) {
            return [
                'id' => $event->id,
                'id_category' => $event->id_category,
                'id_local' => $event->id_local,
                'name' => $event->name,
                'description' => $event->description,
                'date' => $event->date->format('Y-m-d'),
                'time' => $event->time,
                'max_tickets_per_cpf' => $event->max_tickets_per_cpf,
                'category' => $event->category,
                'local' => $event->local,
            ];
        });

        return response()->json($events);
    }

    /**
     * Show a single event.
     */
    public function show(string $id): JsonResponse
    {
        $event = Event::with(['category', 'local'])->findOrFail($id);

        return response()->json([
            'id' => $event->id,
            'id_category' => $event->id_category,
            'id_local' => $event->id_local,
            'name' => $event->name,
            'description' => $event->description,
            'date' => $event->date->format('Y-m-d'),
            'time' => $event->time,
            'max_tickets_per_cpf' => $event->max_tickets_per_cpf,
            'category' => $event->category,
            'local' => $event->local,
        ]);
    }

    /**
     * Store a newly created event.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_category' => ['required', 'integer', 'exists:event_categories,id'],
            'id_local' => ['required', 'integer', 'exists:locals,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'max_tickets_per_cpf' => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $event = Event::create($validator->validated());

        $event->load(['category', 'local']);

        return response()->json([
            'message' => __('Event created successfully.'),
            'event' => [
                'id' => $event->id,
                'id_category' => $event->id_category,
                'id_local' => $event->id_local,
                'name' => $event->name,
                'description' => $event->description,
                'date' => $event->date->format('Y-m-d'),
                'time' => $event->time,
                'max_tickets_per_cpf' => $event->max_tickets_per_cpf,
                'category' => $event->category,
                'local' => $event->local,
            ],
        ], 201);
    }
}
