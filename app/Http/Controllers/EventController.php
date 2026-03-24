<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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

        $events->getCollection()->transform(fn (Event $event) => $this->formatEvent($event));

        return response()->json($events);
    }

    /**
     * Show a single event.
     */
    public function show(Event $event): JsonResponse
    {
        $event->load(['category', 'local']);

        return response()->json($this->formatEvent($event));
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
            'banner_image' => ['nullable', 'image', 'max:5120'],
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

        $eventData = $validator->validated();
        unset($eventData['banner_image']);

        if ($request->hasFile('banner_image')) {
            $bannerImage = $request->file('banner_image');
            $bannerPath = 'events/banners/'.Str::uuid().'.'.$bannerImage->getClientOriginalExtension();

            Storage::disk('supabase')->put($bannerPath, $bannerImage->getContent(), 'public');
            $eventData['banner_image_url'] = Storage::disk('supabase')->url($bannerPath);
        }

        $event = Event::create($eventData);

        $event->load(['category', 'local']);

        return response()->json([
            'message' => __('Event created successfully.'),
            'event' => $this->formatEvent($event),
        ], 201);
    }

    /**
     * Update an event.
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_category' => ['sometimes', 'integer', 'exists:event_categories,id'],
            'id_local' => ['sometimes', 'integer', 'exists:locals,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'banner_image' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'date' => ['sometimes', 'date'],
            'time' => ['sometimes', 'date_format:H:i'],
            'max_tickets_per_cpf' => ['sometimes', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $eventData = $validator->validated();
        unset($eventData['banner_image']);

        if ($request->hasFile('banner_image')) {
            $bannerImage = $request->file('banner_image');
            $bannerPath = 'events/banners/'.Str::uuid().'.'.$bannerImage->getClientOriginalExtension();

            Storage::disk('supabase')->put($bannerPath, $bannerImage->getContent(), 'public');
            $eventData['banner_image_url'] = Storage::disk('supabase')->url($bannerPath);
        }

        $event->update($eventData);
        $event->load(['category', 'local']);

        return response()->json([
            'message' => __('Event updated successfully.'),
            'event' => $this->formatEvent($event),
        ]);
    }

    /**
     * Delete an event.
     */
    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json([
            'message' => __('Event deleted successfully.'),
        ]);
    }

    /**
     * Format event for JSON response.
     *
     * @return array<string, mixed>
     */
    private function formatEvent(Event $event): array
    {
        return [
            'id' => $event->id,
            'id_category' => $event->id_category,
            'id_local' => $event->id_local,
            'name' => $event->name,
            'description' => $event->description,
            'banner_image_url' => $event->banner_image_url,
            'date' => $event->date->format('Y-m-d'),
            'time' => $event->time,
            'max_tickets_per_cpf' => $event->max_tickets_per_cpf,
            'category' => $event->category,
            'local' => $event->local,
        ];
    }
}
