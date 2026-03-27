<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        $events = Event::with(['category', 'local', 'batches'])
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
        $event->load(['category', 'local', 'batches']);

        return response()->json($this->formatEvent($event));
    }

    /**
     * Store a newly created event.
     */
    public function store(Request $request): JsonResponse
    {
        if (is_string($request->input('batches'))) {
            $decoded = json_decode($request->input('batches'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge(['batches' => $decoded]);
            }
        }

        $validator = Validator::make($request->all(), [
            'id_category' => ['required', 'integer', 'exists:event_categories,id'],
            'id_local' => ['required', 'integer', 'exists:locals,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'banner_image' => ['nullable', 'image', 'max:5120'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'max_tickets_per_cpf' => ['required', 'integer', 'min:0'],
            'batches' => ['nullable', 'array', 'min:1'],
            'batches.*.price' => ['required', 'numeric', 'min:0'],
            'batches.*.initial_date' => ['required', 'date'],
            'batches.*.end_date' => ['required', 'date'],
            'batches.*.quantity' => ['required', 'integer', 'min:0'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $batches = $request->input('batches');
            if (!is_array($batches)) {
                return;
            }

            foreach ($batches as $i => $batch) {
                if (!is_array($batch)) {
                    continue;
                }

                $initial = $batch['initial_date'] ?? null;
                $end = $batch['end_date'] ?? null;
                if (!$initial || !$end) {
                    continue;
                }

                try {
                    if (Carbon::parse($end)->lt(Carbon::parse($initial))) {
                        $validator->errors()->add("batches.$i.end_date", __('The end date must be after or equal to the initial date.'));
                    }
                } catch (\Throwable) {
                    // Let the base date rules report invalid dates.
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $eventData = $validator->validated();
        $batchesData = $eventData['batches'] ?? null;
        unset($eventData['banner_image']);
        unset($eventData['batches']);

        if ($request->hasFile('banner_image')) {
            $bannerImage = $request->file('banner_image');
            $bannerPath = 'events/banners/'.Str::uuid().'.'.$bannerImage->getClientOriginalExtension();

            Storage::disk('supabase')->put($bannerPath, $bannerImage->getContent(), 'public');
            $eventData['banner_image_url'] = Storage::disk('supabase')->url($bannerPath);
        }

        /** @var Event $event */
        $event = DB::transaction(function () use ($eventData, $batchesData) {
            $event = Event::create($eventData);

            $batches = $batchesData;
            if (!$batches || count($batches) === 0) {
                $batches = [[
                    'price' => 0,
                    'initial_date' => now()->toDateString(),
                    'end_date' => $event->date->toDateString(),
                    'quantity' => 0,
                ]];
            }

            $event->batches()->createMany($batches);

            return $event;
        });

        $event->load(['category', 'local', 'batches']);

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
        if (is_string($request->input('batches'))) {
            $decoded = json_decode($request->input('batches'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge(['batches' => $decoded]);
            }
        }

        $validator = Validator::make($request->all(), [
            'id_category' => ['sometimes', 'integer', 'exists:event_categories,id'],
            'id_local' => ['sometimes', 'integer', 'exists:locals,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'banner_image' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'date' => ['sometimes', 'date'],
            'time' => ['sometimes', 'date_format:H:i'],
            'max_tickets_per_cpf' => ['sometimes', 'integer', 'min:0'],
            'batches' => ['sometimes', 'array', 'min:1'],
            'batches.*.price' => ['required', 'numeric', 'min:0'],
            'batches.*.initial_date' => ['required', 'date'],
            'batches.*.end_date' => ['required', 'date'],
            'batches.*.quantity' => ['required', 'integer', 'min:0'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $batches = $request->input('batches');
            if (! is_array($batches)) {
                return;
            }

            foreach ($batches as $i => $batch) {
                if (! is_array($batch)) {
                    continue;
                }

                $initial = $batch['initial_date'] ?? null;
                $end = $batch['end_date'] ?? null;
                if (! $initial || ! $end) {
                    continue;
                }

                try {
                    if (Carbon::parse($end)->lt(Carbon::parse($initial))) {
                        $validator->errors()->add("batches.$i.end_date", __('The end date must be after or equal to the initial date.'));
                    }
                } catch (\Throwable) {
                    // Let the base date rules report invalid dates.
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $eventData = $validator->validated();
        $batchesData = $eventData['batches'] ?? null;
        unset($eventData['batches']);
        unset($eventData['banner_image']);

        if ($request->hasFile('banner_image')) {
            $bannerImage = $request->file('banner_image');
            $bannerPath = 'events/banners/'.Str::uuid().'.'.$bannerImage->getClientOriginalExtension();

            Storage::disk('supabase')->put($bannerPath, $bannerImage->getContent(), 'public');
            $eventData['banner_image_url'] = Storage::disk('supabase')->url($bannerPath);
        }

        DB::transaction(function () use ($event, $eventData, $batchesData) {
            if (! empty($eventData)) {
                $event->update($eventData);
            }

            if (is_array($batchesData) && count($batchesData) > 0) {
                $event->batches()->createMany($batchesData);
            }
        });

        $event->refresh();
        $event->load(['category', 'local', 'batches']);

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
            'batches' => $event->batches?->map(fn (Batch $batch) => [
                'id' => $batch->id,
                'price' => (float) $batch->price,
                'initial_date' => $batch->initial_date->format('Y-m-d'),
                'end_date' => $batch->end_date->format('Y-m-d'),
                'quantity' => $batch->quantity,
            ])->values() ?? [],
        ];
    }
}
