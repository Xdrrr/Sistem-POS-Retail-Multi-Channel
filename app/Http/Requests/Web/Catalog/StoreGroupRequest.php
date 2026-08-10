<?php

namespace App\Http\Requests\Web\Catalog;

use App\Models\ProductGroup;
use App\Traits\StoresCatalogImages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupRequest extends FormRequest
{
    use StoresCatalogImages;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(ProductGroup::class, 'name')],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'is_active' => ['boolean'],
        ];
    }
}
