<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MercadoPagoCheckoutSession extends Model
{
    protected $table = 'mercado_pago_checkout_sessions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_user',
        'external_reference',
        'preference_id',
        'cart_snapshot',
        'total_amount',
        'currency_id',
        'status',
        'payment_id',
        'refunded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cart_snapshot' => 'array',
            'total_amount' => 'decimal:2',
            'payment_id' => 'integer',
            'refunded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'id_mercado_pago_checkout_session');
    }
}
