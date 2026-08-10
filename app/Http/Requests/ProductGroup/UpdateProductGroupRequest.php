<?php

namespace App\Http\Requests\ProductGroup;

use App\Http\Requests\ApiFormRequest;
use App\Models\ProductGroup;
use App\Traits\StoresCatalogImages;
use Illuminate\Validation\Rule;

class UpdateProductGroupRequest extends ApiFormRequest
{
    use StoresCatalogImages;

    public function rules(): array
    {
        $group = ProductGroup::query()->where('guid', $this->input('guid'))->first();

        return [
            'guid' => ['required', 'string', Rule::exists(ProductGroup::class, 'guid')],
            'name' => ['required', 'string', 'max:100', Rule::unique(ProductGroup::class, 'name')->ignore($group?->id)],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
