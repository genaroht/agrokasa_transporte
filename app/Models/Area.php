<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $table = 'areas';

    protected $fillable = [
        'nombre',
        'codigo',
        'sucursal_id',   // opcional, si el área es por sucursal
        'responsable',   // nombre del jefe responsable (texto)
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function programaciones()
    {
        return $this->hasMany(Programacion::class);
    }
}
