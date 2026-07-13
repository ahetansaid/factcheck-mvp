<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Personality extends Model
{
    protected $fillable = ['name', 'slug', 'role', 'bio', 'photo_path'];

    protected static function booted(): void
    {
        static::saving(function (self $p) {
            if (blank($p->slug)) {
                $p->slug = Str::slug($p->name);
            }
        });
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }

    /** Nombre de vérifications publiées portant un verdict donné. */
    public function statCount(string $rating): int
    {
        return $this->verifications()->published()->where('rating', $rating)->count();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
