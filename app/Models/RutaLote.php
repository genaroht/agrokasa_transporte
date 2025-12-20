<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RutaLote extends Model
{
    use HasFactory;

    protected $table = 'ruta_lotes';

    protected $fillable = [
        'ruta_id',
        'nombre',
        'comedores',
    ];

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }

    /**
     * Devuelve los comedores como array de strings.
     */
    public function getComedoresListAttribute(): array
    {
        if (!$this->comedores) {
            return [];
        }

        return collect(explode(',', $this->comedores))
            ->map(fn ($c) => trim($c))
            ->filter()
            ->values()
            ->all();
    }
}
