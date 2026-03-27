<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\RefundRequest;
use App\Services\MercadoPago\RefundRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RefundController extends Controller
{
    protected $refundService;

    public function __construct(RefundRequestService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * Comprador solicita reembolso 
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_ticket' => 'required|exists:tickets,id',
            'reason' => 'nullable|string|max:500'
        ]);

        $ticket = Ticket::findOrFail($request->id_ticket);

        // 1. Validação de Propriedade (Só o dono pode pedir) [cite: 50]
        if ($ticket->id_user !== Auth::id()) {
            return response()->json(['message' => 'Este ingresso não pertence a você.'], 403);
        }

        try {
            // 2. Chama o Service para validar prazos e regras do Organizador [cite: 53, 61]
            $this->refundService->validateEligibility($ticket);

            // 3. Cria a solicitação como PENDENTE [cite: 54, 67]
            $refund = RefundRequest::create([
                'id_user' => Auth::id(),
                'id_ticket' => $ticket->id,
                'status' => 'pendente',
                'reason' => $request->reason,
                'amount' => $ticket->batch->price // Valor pago no lote [cite: 36]
            ]);

            return response()->json([
                'message' => 'Solicitação de reembolso enviada com sucesso.',
                'refund' => $refund
            ], 201);

        } catch (\Exception $e) {
            // Retorna erros de validação (ex: fora do prazo) conforme o PDF [cite: 64, 73]
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Lista solicitações do comprador logado [cite: 55-57]
     */
    public function index()
    {
        $refunds = RefundRequest::where('id_user', Auth::id())
            ->with('ticket.event')
            ->get();
            
        return response()->json(['data' => $refunds]);
    }

    public function approve(Request $request, $id)
    {
        $refund = RefundRequest::findOrFail($id);

        // Validação de Segurança: Só o organizador do evento pode aprovar
        if ($refund->ticket->event->id_organizer !== Auth::id()) {
            return response()->json(['message' => 'Você não tem permissão para aprovar este reembolso.'], 403);
        }

        try {
            $this->refundService->processApproval($refund);

            return response()->json([
                'message' => 'Reembolso aprovado e estorno processado com sucesso.',
                'refund' => $refund
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $refund = RefundRequest::findOrFail($id);

        // Validação de Segurança
        if ($refund->ticket->event->id_organizer !== Auth::id()) {
            return response()->json(['message' => 'Você não tem permissão para rejeitar este reembolso.'], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:500' // Obrigatório justificar a rejeição
        ]);

        $refund->update([
            'status' => 'rejeitado',
            'rejection_reason' => $request->reason,
            'processed_at' => now()
        ]);

        return response()->json([
            'message' => 'Reembolso rejeitado com sucesso.',
            'refund' => $refund
        ]);
    }
}