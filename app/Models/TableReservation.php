<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableReservation extends Model
{
    protected $table = 'orders.table_reservations';

    protected $fillable = [
        'guid',
        'table_guid',
        'table_number',
        'customer_name',
        'customer_phone',
        'guest_count',
        'reservation_date',
        'reservation_time',
        'end_time',
        'type',
        'notes',
        'status',
        'guid_cabang',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'guest_count' => 'integer',
            'reservation_date' => 'date:Y-m-d',
            'reservation_time' => 'string',
            'end_time' => 'string',
            'type' => 'string',
            'is_active' => 'boolean',
        ];
    }
}
