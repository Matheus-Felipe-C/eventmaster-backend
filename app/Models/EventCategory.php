<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCategory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    public $timestamps = false;
    
    protected $fillable = [
        'name',
    ];

    /**
     * Get the events for the category.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'id_category');
    }
}
