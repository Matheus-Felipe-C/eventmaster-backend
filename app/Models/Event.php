<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_category',
        'id_local',
        'name',
        'description',
        'banner_image_url',
        'date',
        'time',
        'max_tickets_per_cpf',
        'refund_enabled', 
        'refund_deadline_hours', 
        'refund_requires_approval'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * Get the category that owns the event.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'id_category');
    }

    /**
     * Get the local that owns the event.
     */
    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'id_local');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class, 'id_event');
    }
}
