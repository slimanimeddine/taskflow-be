<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens;

    use HasFactory;
    use HasUuids;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

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

    public function allWorkspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'members', 'user_id', 'workspace_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function adminWorkspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'members', 'user_id', 'workspace_id')
            ->withPivot('role')
            ->wherePivot('role', 'admin')
            ->withTimestamps();
    }

    public function memberWorkspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'members', 'user_id', 'workspace_id')
            ->withPivot('role')
            ->wherePivot('role', 'member')
            ->withTimestamps();
    }
}
