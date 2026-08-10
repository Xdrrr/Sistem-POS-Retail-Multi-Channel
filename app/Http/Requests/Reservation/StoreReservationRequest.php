<?php

namespace App\Http\Requests\Reservation;

use App\Http\Requests\ApiFormRequest;
use App\Models\RestaurantTable;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'table_guid' => ['nullable', 'string', Rule::exists(RestaurantTable::class, 'guid')],
            'table_number' => ['required', 'string', 'max:30'],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'guest_count' => ['nullable', 'integer', 'min:1'],
            'reservation_date' => ['required', 'date'],
            'reservation_time' => ['required', 'string'],
            'end_time' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'in:booking,walkin'],
            'notes' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'in:occupied,pending,confirmed,seated,completed,cancelled'],
            'guid_cabang' => ['nullable', 'string'],
        ];
    }
}
