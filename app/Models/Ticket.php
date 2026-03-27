<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;
    protected $fillable = [
        'id_user',
        'id_event',
        'id_ticket_type',
        'id_batch',
        'status',
        'seat_number',
        'is_validated',
        'payment_id',
        'id_checkout_session',
    ];

    protected $casts = [
        'is_validated' => 'boolean',
        'seat_number' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($ticket) {
            $ticket->ticket_code = (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'id_event');
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class, 'id_ticket_type');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'id_batch');
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class, 'id_ticket');
    }

    public function refundRequests()
    {
        return $this->hasMany(RefundRequest::class, 'id_ticket');
    }
}
