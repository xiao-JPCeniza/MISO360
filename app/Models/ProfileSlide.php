<?php

namespace App\Models;

use App\Enums\ProfileSlideTextPosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileSlide extends Model
{
    use HasFactory;

    protected $fillable = [
        'image_path',
        'title',
        'subtitle',
        'text_position',
        'sort_order',
        'is_active',
        'archived_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'text_position' => ProfileSlideTextPosition::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
