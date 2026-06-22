<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['guid', 'name', 'is_default'])]
class AuthenticationRole extends Model
{
    protected $table = 'authentication.roles';

    public function users(): HasMany
    {
        return $this->hasMany(AuthenticationUser::class, 'role_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'authentication.role_permissions', 'role_id', 'permission_guid', 'id', 'guid')
            ->withTimestamps();
    }

    public function hasPermission(string $name): bool
    {
        return $this->permissions()->where('name', $name)->exists();
    }

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
