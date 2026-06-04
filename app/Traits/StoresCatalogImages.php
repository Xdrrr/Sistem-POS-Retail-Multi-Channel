<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait StoresCatalogImages
{
    protected function imageRule(): array
    {
        return ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
    }

    protected function storeCatalogImage(Request $request, string $folder, ?string $currentImage = null): ?string
    {
        if (! $request->hasFile('image')) {
            return $currentImage;
        }

        if ($currentImage) {
            Storage::disk('public')->delete($currentImage);
        }

        return $request->file('image')->store("catalog/{$folder}", 'public');
    }

    protected function catalogImageUrl(?string $image): ?string
    {
        if (! $image) {
            return null;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        return '/storage/'.ltrim($image, '/');
    }

    protected function deleteCatalogImage(?string $image): void
    {
        if ($image) {
            Storage::disk('public')->delete($image);
        }
    }
}
