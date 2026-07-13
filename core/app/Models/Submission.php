<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    protected $fillable = ['type', 'content', 'contact', 'status', 'verification_id'];

    public const STATUSES = [
        'new' => 'Nouveau',
        'reviewing' => 'En cours',
        'published' => 'Publié',
        'dismissed' => 'Écarté',
    ];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(Verification::class);
    }
}
