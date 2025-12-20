<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    use HasFactory;

    protected $table = 'vehiculos';

    protected $fillable = [
        'sucursal_id',
        'placa',
        'codigo_interno',
        'capacidad',
        'activo',
    ];

    protected $casts = [
        'activo'    => 'boolean',
        'capacidad' => 'integer',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }
}
