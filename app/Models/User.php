<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use BelongsToClinic, Notifiable;

    public const ROLE_OWNER = 'owner';
    public const ROLE_DENTIST = 'dentist';
    public const ROLE_RECEPTIONIST = 'receptionist';
    public const ROLE_ASSISTANT = 'assistant';

    public const ROLES = [
        self::ROLE_OWNER, self::ROLE_DENTIST,
        self::ROLE_RECEPTIONIST, self::ROLE_ASSISTANT,
    ];

    public const CLINICAL_ROLES = [self::ROLE_OWNER, self::ROLE_DENTIST];

    protected $fillable = [
        'clinic_id', 'branch_id', 'name', 'email', 'phone',
        'password', 'role', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['is_active' => 'boolean', 'password' => 'hashed'];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isClinical(): bool
    {
        return in_array($this->role, self::CLINICAL_ROLES, true);
    }
}
