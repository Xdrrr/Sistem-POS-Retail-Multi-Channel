<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['app_name', 'app_key_hash', 'is_active'])]
class ApiClient extends Model
{
    use HasFactory;

    protected $table = 'authentication.api_clients';

    public function tokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
