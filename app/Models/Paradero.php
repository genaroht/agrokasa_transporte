<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paradero extends Model
{
    use HasFactory;

    protected $table = 'paraderos';

    protected $fillable = [
        'sucursal_id',
        'lugar_id',
        'nombre',
        'codigo',
        'direccion',   // la usamos como "referencia" opcional
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function lugar()
    {
        return $this->belongsTo(Lugar::class);
    }

    public function detallesProgramacion()
    {
        return $this->hasMany(ProgramacionDetalle::class);
    }

    /**
     * Accesor de compatibilidad: $paradero->referencia devuelve direccion.
     */
    public function getReferenciaAttribute(): ?string
    {
        return $this->direccion;
    }

    /**
     * Etiqueta útil para combos / reportes.
     * Ej: "Barranca - Los Pinos (frente al mercado) [COD123]"
     */
    public function getEtiquetaCompletaAttribute(): string
    {
        $partes = [];

        if ($this->lugar && $this->lugar->nombre) {
            $partes[] = $this->lugar->nombre;
        }

        if ($this->nombre) {
            $partes[] = $this->nombre;
        }

        if ($this->direccion) { // referencia opcional
            $partes[] = '(' . $this->direccion . ')';
        }

        if ($this->codigo) {
            $partes[] = '[' . $this->codigo . ']';
        }

        return trim(implode(' - ', $partes));
    }
}
