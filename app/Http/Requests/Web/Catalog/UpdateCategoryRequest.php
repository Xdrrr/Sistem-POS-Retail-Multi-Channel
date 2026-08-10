<?php

namespace App\Http\Requests\Web\Catalog;

use App\Models\Category;
use App\Traits\StoresCatalogImages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    use StoresCatalogImages;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = Category::query()->where('guid', $this->route('guid'))->first();

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(Category::class, 'name')->ignore($category?->id)],
            'description' => ['nullable', 'string'],
            'image' => $this->imageRule(),
            'is_active' => ['boolean'],
        ];
    }
}
