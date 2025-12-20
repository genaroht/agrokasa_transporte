<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Programacion extends Model
{
    use HasFactory;

    public const TIPO_RECOJO = 'recojo';
    public const TIPO_SALIDA = 'salida';

    protected $table = 'programaciones';

    protected $fillable = [
        'sucursal_id',
        'fecha',
        'area_id',
        'horario_id',
        'estado',
        'tipo',             // recojo | salida
        'total_personas',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'fecha'          => 'date',
        'total_personas' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function horario()
    {
        return $this->belongsTo(Horario::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function detalles()
    {
        return $this->hasMany(ProgramacionDetalle::class);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Recalcula el total de personas en función de los detalles.
     */
    public function recalcularTotalPersonas(): void
    {
        $total = $this->detalles()->sum('personas');
        $this->total_personas = $total;
        $this->save();
    }

    /**
     * ¿Está cerrada? (no se puede editar)
     */
    public function estaCerrada(): bool
    {
        return $this->estado === 'cerrado';
    }

    /**
     * ¿Está confirmada?
     */
    public function estaConfirmada(): bool
    {
        return $this->estado === 'confirmado';
    }

    /**
     * Helpers por tipo.
     */
    public function esRecojo(): bool
    {
        return $this->tipo === self::TIPO_RECOJO;
    }

    public function esSalida(): bool
    {
        return $this->tipo === self::TIPO_SALIDA;
    }

    /**
     * Etiqueta bonita para mostrar el tipo.
     */
    public function getTipoLabelAttribute(): string
    {
        if ($this->esSalida()) {
            return 'Salida (retorno a la ciudad)';
        }

        return 'Recojo (entrada al fundo)';
    }
}
