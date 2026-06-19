<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    protected $table = 'orders.tables';

    protected $fillable = [
        'guid',
        'table_number',
        'capacity',
        'location',
        'status',
        'guid_cabang',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function resolveStatus(): string
    {
        if ($this->status === 'maintenance') {
            return 'maintenance';
        }

        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i');

        $hasOpenOrder = Order::query()
            ->where('table_number', $this->table_number)
            ->where('status', 'open')
            ->exists();

        if ($hasOpenOrder) {
            return 'occupied';
        }

        $hasReservation = TableReservation::query()
            ->where('table_guid', $this->guid)
            ->where('reservation_date', $today)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('is_active', true)
            ->exists();

        if ($hasReservation) {
            return 'reserved';
        }

        return 'available';
    }

}
