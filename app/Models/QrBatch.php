<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrBatch extends Model
{
    protected $fillable = [
        'start_sequence',
        'end_sequence',
    ];

    public function getIdsAttribute(): array
    {
        $ids = [];
        for ($seq = $this->start_sequence; $seq <= $this->end_sequence; $seq++) {
            $ids[] = sprintf('MIS-UID-%05d', $seq);
        }

        return $ids;
    }
}
