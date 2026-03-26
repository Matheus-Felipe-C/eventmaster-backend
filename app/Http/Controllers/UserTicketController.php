<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\TicketResource;

class UserTicketController extends Controller
{
    /**
     * Display a listing of the user's tickets.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request)
    {
        // Get the authenticated user's ID
        // We explicitly do not trust any 'id_user' that might be in the request
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Fetch tickets belonging ONLY to the authenticated user
        $tickets = Ticket::where('id_user', $userId)->get();

        return TicketResource::collection($tickets);
    }

    public function validateTicket(Request $request)
{
    $request->validate([
        'ticket_code' => 'required|uuid|exists:tickets,ticket_code',
    ]);

    $ticket = Ticket::where('ticket_code', $request->ticket_code)->first();

    // Regra 1: O ingresso precisa estar pago
    if ($ticket->status !== 'paid') {
        return response()->json(['message' => 'Este ingresso ainda não foi pago.'], 403);
    }

    // Regra 2: Evitar entrada duplicada (Fraude)
    if ($ticket->is_validated) {
        return response()->json(['message' => 'Este ingresso já foi utilizado.'], 400);
    }

    // Sucesso: Validar entrada
    $ticket->update(['is_validated' => true]);

    return response()->json([
        'message' => 'Entrada autorizada com sucesso!',
        'ticket' => [
            'id' => $ticket->id,
            'seat' => $ticket->seat_number,
            'validated_at' => now()->format('d/m/Y H:i:s')
        ]
    ]);
    }
}
