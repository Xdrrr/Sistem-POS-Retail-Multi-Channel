<?php

namespace App\Http\Requests\Reservation;

use App\Http\Requests\ApiFormRequest;

class IndexReservationRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'filter' => ['nullable', 'array'],
            'filter.set_guid' => ['nullable', 'boolean'],
            'filter.guid' => ['nullable', 'string'],
            'filter.set_table_number' => ['nullable', 'boolean'],
            'filter.table_number' => ['nullable', 'string', 'max:30'],
            'filter.set_status' => ['nullable', 'boolean'],
            'filter.status' => ['nullable', 'string', 'in:occupied,pending,confirmed,seated,completed,cancelled'],
            'filter.set_reservation_date' => ['nullable', 'boolean'],
            'filter.reservation_date' => ['nullable', 'date'],
            'filter.set_guid_cabang' => ['nullable', 'boolean'],
            'filter.guid_cabang' => ['nullable', 'string'],
            'filter.set_is_active' => ['nullable', 'boolean'],
            'filter.is_active' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:table_number,customer_name,reservation_date,reservation_time,status,guest_count,created_at'],
            'sort' => ['nullable', 'string', 'in:ASC,DESC'],
        ];
    }
}
