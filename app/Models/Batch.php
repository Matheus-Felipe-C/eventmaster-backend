<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_event',
        'price',
        'initial_date',
        'end_date',
        'quantity',
    ];

    protected $casts = [
        'initial_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:2',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'id_batch');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'id_event');
    }
}
