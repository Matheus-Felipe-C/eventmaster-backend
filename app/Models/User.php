<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_role',
        'name',
        'cpf',
        'email',
        'phone_number',
        'reason',
        'password',
    ];

    /**
     * Get the role that owns the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_role');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'id_user');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'id_user');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Verify if the user is an admin.
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role->name === 'admin';
    }

    /**
     * Verify if the user is a root user.
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->role->name === 'root';
    }

    /**
     * Verify if the user is an organizer.
     */
    public function isOrganizer(): bool
    {
        return $this->role->name === 'organizer';
    }

    /**
     * Revoke all tokens and delete the user. Use for both self-deletion and admin deletion.
     */
    public function deleteAccount(): bool
    {
        $this->tokens()->delete();

        return $this->delete();
    }
}
