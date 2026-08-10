<?php

namespace App\Http\Requests\Web\Catalog;

use App\Models\Category;
use App\Traits\StoresCatalogImages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    use StoresCatalogImages;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(Category::class, 'name')],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'is_active' => ['boolean'],
        ];
    }
}
