<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    use HasFactory;

    protected $table = 'rutas';

    protected $fillable = [
        'sucursal_id',
        'codigo',
        'nombre',
        'id_vehiculo',   // FK al vehículo
        'activo',
        // 'observaciones', // si tienes esta u otras columnas, agrégalas aquí
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Sucursal a la que pertenece la ruta.
     */
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    /**
     * Vehículo asignado a la ruta.
     */
    public function vehiculo()
    {
        // Si tu columna FK se llama "id_vehiculo", esto está perfecto.
        // Si se llama distinto (ej. id_vehiculo), usa:
        // return $this->belongsTo(Vehiculo::class, 'id_vehiculo');
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo');
    }

    /**
     * Programaciones que usan esta ruta (si la relación existe).
     */
    public function programaciones()
    {
        return $this->hasMany(Programacion::class);
    }
    public function lotes()
    {
        return $this->hasMany(RutaLote::class);
    }

}
