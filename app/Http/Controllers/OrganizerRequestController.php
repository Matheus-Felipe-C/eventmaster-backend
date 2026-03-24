<?php

namespace App\Http\Controllers;

use App\Models\OrganizerRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrganizerRequestController extends Controller
{
    /**
     * Submit a new organizer request (public).
     * Organizers are natural persons: name, CPF, email, optional phone and reason.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', 'max:14', 'unique:organizer_requests,cpf'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:organizer_requests,email'],
            'phone_number' => ['nullable', 'string', 'max:32'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $organizerRequest = OrganizerRequest::create($validator->validated());

        return response()->json([
            'message' => __('Organizer request submitted successfully.'),
            'organizer_request' => $organizerRequest,
        ], 201);
    }

    /**
     * List all organizer requests (admin).
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrganizerRequest::query()->orderByDesc('created_at');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $requests = $query->paginate($request->integer('per_page', 15));

        return response()->json($requests);
    }

    /**
     * Show a single organizer request (admin).
     */
    public function show(OrganizerRequest $organizerRequest): JsonResponse
    {
        return response()->json($organizerRequest);
    }

    /**
     * Approve an organizer request (admin).
     * If a user with matching email and CPF exists, promotes them to organizer; otherwise creates a new user with a temporary password.
     */
    public function approve(OrganizerRequest $organizerRequest): JsonResponse
    {
        if ($organizerRequest->status !== 'pending') {
            return response()->json([
                'message' => $this->organizerRequestNotPendingMessage($organizerRequest),
            ], 422);
        }

        if (blank($organizerRequest->cpf) || blank($organizerRequest->email)) {
            return response()->json([
                'message' => __('This request is missing CPF or email and cannot be approved.'),
            ], 422);
        }

        $role = Role::where('name', 'organizer')->first();

        if (! $role) {
            return response()->json([
                'message' => __('Organizer role not configured.'),
            ], 500);
        }

        $byEmail = User::where('email', $organizerRequest->email)->first();
        $byCpf = User::where('cpf', $organizerRequest->cpf)->first();

        if ($byEmail && $byCpf && $byEmail->id !== $byCpf->id) {
            return response()->json([
                'message' => __('Another account already uses this email or CPF.'),
            ], 409);
        }

        $user = $byEmail ?? $byCpf;

        if ($user) {
            $user->load('role');

            if ($user->email !== $organizerRequest->email || $user->cpf !== $organizerRequest->cpf) {
                return response()->json([
                    'message' => __('The request data does not match the existing account.'),
                ], 409);
            }

            if ($user->isRoot() || $user->isAdmin()) {
                return response()->json([
                    'message' => __('This user cannot be promoted to organizer through a request.'),
                ], 422);
            }

            if (! $user->isOrganizer()) {
                $user->id_role = $role->id;
                $user->name = $organizerRequest->name;
                $user->save();
            }

            $organizerRequest->update(['status' => 'approved']);
            $user->refresh();
            $user->load('role');

            return response()->json([
                'message' => __('Organizer request approved successfully.'),
                'user' => [
                    'id' => $user->id,
                    'id_role' => $user->id_role,
                    'role' => $user->role->name,
                    'name' => $user->name,
                    'cpf' => $user->cpf,
                    'email' => $user->email,
                ],
                'existing_account' => true,
            ]);
        }

        $temporaryPassword = Str::random(16);

        $user = User::create([
            'id_role' => $role->id,
            'name' => $organizerRequest->name,
            'cpf' => $organizerRequest->cpf,
            'email' => $organizerRequest->email,
            'password' => Hash::make($temporaryPassword),
        ]);

        $organizerRequest->update(['status' => 'approved']);

        $user->load('role');

        return response()->json([
            'message' => __('Organizer request approved successfully.'),
            'user' => [
                'id' => $user->id,
                'id_role' => $user->id_role,
                'role' => $user->role->name,
                'name' => $user->name,
                'cpf' => $user->cpf,
                'email' => $user->email,
            ],
            'existing_account' => false,
            'temporary_password' => $temporaryPassword,
        ]);
    }

    /**
     * Reject an organizer request (admin).
     */
    public function reject(OrganizerRequest $organizerRequest): JsonResponse
    {
        if ($organizerRequest->status !== 'pending') {
            return response()->json([
                'message' => $this->organizerRequestNotPendingMessage($organizerRequest),
            ], 422);
        }

        $organizerRequest->update(['status' => 'rejected']);

        return response()->json([
            'message' => __('Organizer request rejected successfully.'),
        ]);
    }

    private function organizerRequestNotPendingMessage(OrganizerRequest $organizerRequest): string
    {
        return match ($organizerRequest->status) {
            'approved' => __('This organizer request has already been approved.'),
            'rejected' => __('This organizer request has already been rejected.'),
            default => __('This organizer request is no longer pending.'),
        };
    }
}
