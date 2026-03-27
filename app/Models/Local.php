<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Local extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
  public $timestamps = false;

    protected $fillable = [
        'name',
        'street',
        'number_street',
        'neighborhood',
        'max_people',
    ];

    /**
     * Get the events for the local.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'id_local');
    }
}
