<?php

namespace App\Services\MercadoPago;

use App\Models\Ticket;
use Carbon\Carbon;
use Exception;

class RefundRequestService
{
    /**
     * Valida se um ingresso é elegível para reembolso com base nas regras do PDF.
     */
    public function validateEligibility(Ticket $ticket)
    {
        // 1. O ingresso precisa estar pago 
        if ($ticket->status !== 'paid') {
            throw new Exception("Apenas ingressos pagos podem ser reembolsados.");
        }

        // 2. O ingresso não pode ter sido usado (check-in) 
        if ($ticket->is_validated) {
            throw new Exception("Ingressos já validados não podem ser reembolsados.");
        }

        $event = $ticket->event;

        // 3. Verificar se o reembolso está habilitado para o evento 
        if (!$event->refund_enabled) {
            throw new Exception("O reembolso não está habilitado para este evento.");
        }

        // 4. Validar o prazo (Refund Deadline) 
        $dateStr = $event->date instanceof \Carbon\Carbon ? $event->date->format('Y-m-d') : substr((string)$event->date, 0, 10);
        $eventDateTime = Carbon::parse($dateStr . ' ' . $event->time);
        $deadline = $eventDateTime->subHours($event->refund_deadline_hours);

        if (now()->isAfter($deadline)) {
            throw new Exception("O prazo para solicitação de reembolso expirou.");
        }

        // 5. Verificar se já existe uma solicitação pendente 
        if ($ticket->refundRequests()->where('status', 'pendente')->exists()) {
            throw new Exception("Já existe uma solicitação de reembolso em análise para este ingresso.");
        }

        return true;
    }

    public function approve(Request $request, $id)
    {
        $refund = RefundRequest::with('ticket.event', 'ticket.batch')->findOrFail($id);

        // Validação de permissão: O usuário é o organizador deste evento?
        if ($refund->ticket->event->id_organizer !== Auth::id()) {
            return response()->json(['message' => 'Você não tem permissão para gerir este evento.'], 403);
        }

        if ($refund->status !== 'pendente') {
            return response()->json(['message' => 'Esta solicitação já foi processada.'], 400);
        }

        try {
            $this->refundService->processApproval($refund);
            return response()->json(['message' => 'Reembolso aprovado e processado com sucesso.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao processar com Mercado Pago: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Organizador recusa o reembolso 
     */
    public function reject(Request $request, $id)
    {
        $request->validate(['organizer_note' => 'required|string|max:500']);

        $refund = RefundRequest::findOrFail($id);

        if ($refund->ticket->event->id_organizer !== Auth::id()) {
            return response()->json(['message' => 'Sem permissão.'], 403);
        }

        $refund->update([
            'status' => 'recusado',
            'organizer_note' => $request->organizer_note,
            'processed_at' => now()
        ]);

        return response()->json(['message' => 'Solicitação de reembolso recusada.']);
    }
}