<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProgramacionDetalle extends Model
{
    use HasFactory;

    protected $table = 'programacion_detalles';

    protected $fillable = [
        'programacion_id',
        'paradero_id',
        'ruta_id',
        'lote',
        'comedor',
        'personas',
    ];

    protected $casts = [
        'personas' => 'integer',
    ];

    public function programacion()
    {
        return $this->belongsTo(Programacion::class);
    }

    public function paradero()
    {
        return $this->belongsTo(Paradero::class);
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }
}
