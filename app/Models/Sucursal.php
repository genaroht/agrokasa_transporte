<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use HasFactory;

    protected $table = 'sucursales';

    protected $fillable = [
        'codigo',
        'nombre',
        'direccion',
        'timezone',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    # Relaciones útiles

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function paraderos()
    {
        return $this->hasMany(Paradero::class);
    }

    public function lugares()
    {
        return $this->hasMany(Lugar::class);
    }

    public function areas()
    {
        return $this->hasMany(Area::class);
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class);
    }

    public function programaciones()
    {
        return $this->hasMany(Programacion::class);
    }

    /**
     * Etiqueta cómoda para combos.
     */
    public function getEtiquetaAttribute(): string
    {
        $txt = $this->nombre;

        if ($this->codigo) {
            $txt .= ' [' . $this->codigo . ']';
        }

        return $txt;
    }
}
