<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['guid', 'name', 'is_default'])]
class AuthenticationRole extends Model
{
    protected $table = 'authentication.roles';

    public function users(): HasMany
    {
        return $this->hasMany(AuthenticationUser::class, 'role_id');
    }

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
