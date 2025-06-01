<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workspace extends Model
{
    /** @use HasFactory<\Database\Factories\WorkspaceFactory> */
    use HasFactory;

    use HasUuids;

    protected $fillable = [
        'name',
        'image_path',
        'user_id',
        'invite_code',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
