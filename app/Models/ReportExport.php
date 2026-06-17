<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportExport extends Model
{
    protected $fillable = [
        'guid',
        'type',
        'status',
        'filters',
        'format',
        'file_path',
        'row_count',
        'error_message',
        'requested_by',
        'guid_cabang',
        'started_at',
        'finished_at',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'row_count' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }
}
