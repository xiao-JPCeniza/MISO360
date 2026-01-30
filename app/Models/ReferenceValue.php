<?php

namespace App\Models;

use App\Enums\ReferenceValueGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferenceValue extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'group_key',
        'name',
        'system_seeded',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
            'system_seeded' => 'bool',
        ];
    }

    public function scopeForGroup(Builder $query, ReferenceValueGroup|string $group): Builder
    {
        $groupKey = $group instanceof ReferenceValueGroup ? $group->value : $group;

        return $query->where('group_key', $groupKey);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
