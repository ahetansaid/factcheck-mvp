<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Verification extends Model
{
    protected $fillable = [
        'title', 'slug', 'claim', 'rating', 'summary', 'body', 'category',
        'personality_id', 'author_id', 'status', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /** Verdict interne => libellé FR + valeur schema.org (1 = faux … 5 = vrai). */
    public const RATINGS = [
        'false'      => ['label' => 'Faux',        'value' => 1],
        'misleading' => ['label' => 'Trompeur',    'value' => 2],
        'unproven'   => ['label' => 'Non vérifié', 'value' => 3],
        'true'       => ['label' => 'Vrai',        'value' => 5],
    ];

    protected static function booted(): void
    {
        static::saving(function (self $v) {
            if (blank($v->slug)) {
                $v->slug = Str::slug($v->title);
            }
        });
    }

    public function ratingLabel(): string
    {
        return self::RATINGS[$this->rating]['label'] ?? $this->rating;
    }

    public function ratingValue(): int
    {
        return self::RATINGS[$this->rating]['value'] ?? 3;
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')->whereNotNull('published_at');
    }

    public function personality(): BelongsTo
    {
        return $this->belongsTo(Personality::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(Source::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
