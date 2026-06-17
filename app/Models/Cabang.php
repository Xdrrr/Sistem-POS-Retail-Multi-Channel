<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    protected $table = 'authentication.cabang';

    protected $fillable = [
        'guid',
        'kode',
        'nama',
        'alamat',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public static function listActive(): array
    {
        return self::query()
            ->where('is_active', true)
            ->orderBy('kode')
            ->get(['guid', 'kode', 'nama'])
            ->map(fn (self $c): array => [
                'guid' => $c->guid,
                'kode' => $c->kode,
                'nama' => $c->nama,
            ])
            ->values()
            ->all();
    }
}
