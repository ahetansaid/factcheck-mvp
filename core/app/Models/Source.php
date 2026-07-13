<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Source extends Model
{
    protected $fillable = ['verification_id', 'title', 'url'];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(Verification::class);
    }
}
