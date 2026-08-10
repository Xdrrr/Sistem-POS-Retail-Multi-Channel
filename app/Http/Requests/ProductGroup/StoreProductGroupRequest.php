<?php

namespace App\Http\Requests\ProductGroup;

use App\Http\Requests\ApiFormRequest;
use App\Models\ProductGroup;
use App\Traits\StoresCatalogImages;
use Illuminate\Validation\Rule;

class StoreProductGroupRequest extends ApiFormRequest
{
    use StoresCatalogImages;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(ProductGroup::class, 'name')],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
