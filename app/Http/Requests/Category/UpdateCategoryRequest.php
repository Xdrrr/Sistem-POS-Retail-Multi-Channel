<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\ApiFormRequest;
use App\Models\Category;
use App\Traits\StoresCatalogImages;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends ApiFormRequest
{
    use StoresCatalogImages;

    public function rules(): array
    {
        $category = Category::query()->where('guid', $this->input('guid'))->first();

        return [
            'guid' => ['required', 'string', Rule::exists(Category::class, 'guid')],
            'name' => ['required', 'string', 'max:100', Rule::unique(Category::class, 'name')->ignore($category?->id)],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
