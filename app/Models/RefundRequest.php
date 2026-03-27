<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_user',
        'id_ticket',
        'status',
        'reason',
        'organizer_note',
        'mercado_pago_refund_id',
        'amount',
        'processed_at'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    // Relacionamento com o Usuário (quem pediu)
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Relacionamento com o Ingresso
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'id_ticket');
    }
}