<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\ApiFormRequest;
use App\Models\Category;
use App\Traits\StoresCatalogImages;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends ApiFormRequest
{
    use StoresCatalogImages;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(Category::class, 'name')],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
