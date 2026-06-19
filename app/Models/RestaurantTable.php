<?php

namespace App\Models;

use App\Events\TableStatusChanged;
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

    public static function broadcastByTableNumber(string $tableNumber, string $action = 'updated'): void
    {
        $table = self::query()->where('table_number', $tableNumber)->first();
        if ($table) {
            TableStatusChanged::dispatch([
                'guid' => $table->guid,
                'table_number' => $table->table_number,
                'capacity' => $table->capacity,
                'location' => $table->location,
                'status' => $table->resolveStatus(),
                'guid_cabang' => $table->guid_cabang,
                'is_active' => $table->is_active,
                'created_at' => $table->created_at?->toISOString(),
                'updated_at' => $table->updated_at?->toISOString(),
            ], $action);
        }
    }
}
