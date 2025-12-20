<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramacionRutaLote extends Model
{
    use HasFactory;

    protected $table = 'programacion_ruta_lote';

    /**
     * Uso $guarded = [] para no pelear con nombres de columnas.
     * Si quieres más seguridad, cámbialo por $fillable con tus campos exactos.
     */
    protected $guarded = [];

    public function programacion()
    {
        return $this->belongsTo(Programacion::class);
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }
}
