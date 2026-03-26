<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use SimpleSoftwareIO\QrCode\Facades\QrCode; // Importação essencial

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'seat' => $this->seat_number,
            
            // Texto do QR (UUID) - Útil para logs ou conferência manual
            'ticket_code' => $this->when($this->status === 'paid', $this->ticket_code),

            // A IMAGEM DO QR CODE: Gerada em tempo real apenas se estiver pago
            'qr_code_svg' => $this->when($this->status === 'paid', function() {
                return QrCode::size(200)
                    ->color(0, 0, 0)
                    ->generate($this->ticket_code)
                    ->toHtml();
            }),

            'is_validated' => (bool) $this->is_validated,
            'event_id' => $this->id_event,
        ];
    }
}